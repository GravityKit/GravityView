<?php
/*
Standalone PHPEnkoder
Version: 1.0.0
Author: Jonathan Nicol @f6design
https://github.com/jnicol/standalone-php-enkoder

Uses PHP to encode email addresses so they can't be read by
spambots. Encodes plaintext email addresses and wraps them in a
mailto: link, and obfuscates any pre-existing mailto: links.

This is a standalone version of Michael Greenberg's excellent
PHPEnkoder WordPress plugin, based on Hivelogic Enkoder.

Usage:
require_once('StandalonePHPEnkoder.php');
$enkoder = new StandalonePHPEnkoder();
$cleaned =  $enkoder->enkodeAllEmails($text);

If you only want to encode mailtos:
$enkoder->enkodeMailtos($text);

If you only want to encode plaintext emails:
$enkoder->enkodePlaintextEmails($text);

LICENSE (Modified BSD)
Copyright (c) 2013, Jonathan Nicol. Derivative work of Michael
Greenberg's PHPEnkoder plugin for WordPress, Copyright (c) 2006-11,
Michael Greenberg, which is itself aderivative work of Hivelogic
Enkoder, Copyright (c) 2006, Automatic Corp. All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are
met:

	1. Redistributions of source code must retain the above copyright
	notice, this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	notice, this list of conditions and the following disclaimer in
	the documentation and/or other materials provided with the
	distribution.

	3. Neither the name of Jonathan Nicol, Michael Greenberg, AUTOMATIC
	CORP. nor the names of its contributors may be used to endorse
	or promote products derived from this software without specific
	prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
*/
class StandalonePHPEnkoder {
	public $enkode_msg    = 'email hidden; JavaScript is required';
	public $enkode_class  = 'enkoded-mailto';
	public $max_passes    = 20;
	public $max_length    = 1024;
	private $min_length   = 269;
	private $enkoder_uses = 0;
	private $email_regex;
	private $ptext_email;
	private $mailto_email;
	private $link_text;
	private $enk_dec_reverse;
	private $enk_dec_num;
	private $enk_dec_swap;
	private $enkodings;

	public function __construct() {
		$this->email_regex = '[\w\d+_.-]+@(?:[\w\d_-]+\.)+[\w]{2,6}';
		// First matching group specifies banned first characters
		// Second matching group excludes email address preceded by =" i.e. src="img@2x.gif"
		$this->ptext_email  = '/(?<=[^\/\w\d\+_.:-])(?<!=")(' . $this->email_regex . ')/i';
		$this->mailto_email = '#(<a[^<>]*?href=[\042\047]mailto:' . $this->email_regex . '[^<>]*?>.*?</a>)#i';
		$this->link_text    = '#/>(.*?)</a#i';

		// Encoding list
		// Listed fully below.  Add in this format to get new phases, which will
		// be used automatically by the enkode function.
		$this->enk_dec_reverse = <<<EOT
kode=kode.split('').reverse().join('')
EOT;
		$this->enk_dec_num     = <<<EOT
kode=kode.split(' ');x='';for(i=0;i<kode.length;i++){x+=String.fromCharCode(parseInt(kode[i])-3)}kode=x
EOT;
		$this->enk_dec_swap    = <<<EOT
x='';for(i=0;i<(kode.length-1);i+=2){x+=kode.charAt(i+1)+kode.charAt(i)}kode=x+(i<kode.length?kode.charAt(kode.length-1):'')
EOT;
		$this->enkodings       = array(
			array( 'enkEncReverse', $this->enk_dec_reverse ),
			array( 'enkEncNum', $this->enk_dec_num ),
			array( 'enkEncSwap', $this->enk_dec_swap ),
		);
	}

	/**
	 * Enkode plaintext emails
	 *
	 * Encodes all plaintext e-mails into a JavaScript-obscured mailto; the
	 * text of the mailto: is the e-mail address itself.
	 */
	public function enkodePlaintextEmails( $text ) {
		return preg_replace_callback( $this->ptext_email, array( $this, 'enkEmailToLink' ), $text );
	}

	/**
	 * Enkode mailto: links
	 *
	 * Encodes all mailto: links into JavaScript obscured text.
	 */
	public function enkodeMailtos( $text ) {
		return preg_replace_callback( $this->mailto_email, array( $this, 'enkPlaintextLink' ), $text );
	}

	/**
	 * Enkode all emails
	 *
	 * Encodes all mailto: and plaintext links into JavaScript obscured text.
	 */
	public function enkodeAllEmails( $text ) {
		$js = $this->enkodeMailtos( $text );
		$js = $this->enkodePlaintextEmails( $js );

		return $js;
	}

	/**
	 * Extract link text
	 */
	private function enkExtractLinktext( $text ) {
		if ( preg_match( $this->link_text, $text, $tmatches ) ) {
			return $tmatches[1];
		}
		return null;
	}

	/**
	 * Enkode a single mailto: link
	 */
	private function enkEmailToLink( $matches ) {
		return $this->enkodeMailto( $matches[1], $matches[1] );
	}

	/**
	 * Enkode a single plaintext link
	 */
	private function enkPlaintextLink( $matches ) {
		$text = $this->enkExtractLinktext( $matches[1] );
		return $this->enkode( $matches[1], $text );
	}

	/**
	 * Enkode a mailto: link
	 */
	public function enkodeMailto( $email, $text, $subject = '', $title = '' ) {
		$content = '<a class="' . $this->enkode_class . '" href="mailto:' . $email;
		if ( $subject ) {
			$content .= '?subject=' . $subject;
		}
		$content .= '"';
		if ( $title ) {
			$content .= ' title="' . $title . '"';
		}
		$content .= '>' . $text . '</a>';

		return $this->enkode( $content );
	}

	/**
	 * Enkode
	 *
	 * Encodes a string to be view-time written by obfuscated Javascript.
	 * The max passes parameter is a tight bound on the number of encodings
	 * perormed. The max length paramater is a loose bound on the length of
	 * the generated Javascript. Setting it to 0 will use a single pass of
	 * enkEncNum.
	 *
	 * The function works by selecting encodings at random from the array
	 * enkodings, applying them to the given string, and then producing
	 * Javascript to decode. The Javascript works by recursive evaluation,
	 * which should be nasty enough to stop anything but the most determined
	 * spambots.
	 *
	 * The text parameter, if set, overrides the user-settable option
	 * enk_msg. This is the message overwritten by the JavaScript; if a
	 * browser doesn't support JavaScript, this message will be shown to the
	 * user.
	 */
	public function enkode( $content, $text = null ) {
		$max_passes = $this->max_passes;
		$max_length = $this->max_length;

		// Our base case -- we'll eventually evaluate this code.
		// Note that we're using innerHTML() since document.write() fails on
		// pages loaded using AJAX.
		$kode = "document.getElementById('ENKODER_ID').outerHTML=\"" .
		addcslashes( $content, "\\\'\"&\n\r<>" ) .
		'";';

		$max_length = max( $max_length, strlen( $kode ) + $this->min_length + 1 );

		$result = '';

		// Build up as many encodings as we can.
		for ( $passes = 0; $passes < $max_passes && strlen( $kode ) < $max_length; $passes++ ) {
			// Pick an encoding at random.
			$idx  = rand( 0, count( $this->enkodings ) - 1 );
			$enc  = $this->enkodings[ $idx ][0];
			$dec  = $this->enkodings[ $idx ][1];
			$kode = $this->enkodePass( $kode, $enc, $dec );
		}

		// Mandatory numerical encoding, prevents catching @ signs and
		// interpreting neighboring characters as e-mail addresses.
		$kode = $this->enkodePass( $kode, 'enkEncNum', $this->enk_dec_num );

		return $this->enkBuildJS( $kode, $text );
	}

	/**
	 * Encode a single pass
	 *
	 * $enc is a function pointer and $dec is the Javascript.
	 */
	private function enkodePass( $kode, $enc, $dec ) {
		// First encode.
		$kode = addslashes( $this->$enc( $kode ) );

		// Then generate encoded code with decoding afterwards.
		$kode = "kode=\"$kode\";$dec;";

		return $kode;
	}

	/**
	 * Build JavaScript
	 *
	 * Generates the Javascript recursive evaluator, which is 269 characters
	 * of boilerplate code.
	 *
	 * Unfortunately, <noscript> can't be used arbitrarily in XHTML.  A
	 * <span> that we immediately overwrite, serves as an ad hoc <noscript>
	 * tag.
	 */
	private function enkBuildJS( $kode, $text = null ) {
		$clean = addslashes( $kode );

		$msg = is_null( $text ) ? $this->enkode_msg : $text;

		$name                = 'enkoder_' . strval( $this->enkoder_uses ) . '_' . strval( rand() );
		$this->enkoder_uses += 1;
		// Note that we decode until $kode contains "getElementById('ENKODER_ID')",
		// at which point we replace ENKODER_ID with the ID of our span, then
		// perform the final eval().
		$js = <<<EOT
<span id="$name">$msg</span><script id="script_{$name}" type="text/javascript">
/* <!-- */
function hivelogic_$name() {
var kode="$clean",i,c,x,script=document.getElementById("script_{$name}");while(kode.indexOf("getElementById('ENKODER_ID')")===-1){eval(kode)};kode=kode.replace('ENKODER_ID','$name');eval(kode);script.parentNode.removeChild(script);
}
hivelogic_$name();
/* --> */
</script>
EOT;

		return $js;
	}
	/**
	 * Encodings
	 *
	 * Each encoding should consist of a function and a Javascript string;
	 * the function performs some scrambling of a string, and the Javascript
	 * unscrambles that string (assuming that it's stored in a variable
	 * kode). The listed enkodings are those used in the Hivelogic Enkoder.
	 *
	 * THe JS strings are defined as class variables $enk_dec_reverse,
	 * $enk_dec_num and $enk_dec_swap.
	 */

	/**
	 * Reverse encoding
	 */
	private function enkEncReverse( $s ) {
		return strrev( $s );
	}

	/**
	 * Num encoding (adapted)
	 */
	private function enkEncNum( $s ) {
		$nums = '';

		$len = strlen( $s );
		for ( $i = 0;$i < $len;$i++ ) {
			$nums .= strval( ord( $s[ $i ] ) + 3 );
			if ( $i < $len - 1 ) {
				$nums .= ' '; }
		}

		return $nums;
	}

	/**
	 * Swap encoding
	 */
	private function enkEncSwap( $s ) {
		$swapped = strval( $s );

		$len = strlen( $s );
		for ( $i = 0;$i < $len - 1;$i += 2 ) {
			$tmp               = $swapped[ $i + 1 ];
			$swapped[ $i + 1 ] = $swapped[ $i ];
			$swapped[ $i ]     = $tmp;
		}

		return $swapped;
	}
}
