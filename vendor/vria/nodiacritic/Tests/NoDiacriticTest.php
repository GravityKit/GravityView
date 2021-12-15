<?php

use VRia\Utils\NoDiacritic;
	
class NoDiacriticTest extends \PHPUnit_Framework_TestCase
{
    const NO_DIACRITIC = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ,./?'\"!@#$%^&*()_-+=абвгдеёжзийклмнопрстуфхцчшщъыьэюяАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯ";
    const DIACRITIC = "àâçéèêëîïœôùûÀÂÇÈÉÊËÎÏŒáéíñóúüÁÉÍÑÓÚÜÔÙÛàèìòùÀÈÌÒÙãÃçÇòÒóÓõÕäåæðëöøßþüÿÄÅÆÐËÖØÞÜ";

    public function testDoNotReplaceCharsDefaultLocale()
    {
        $this->assertEquals(self::NO_DIACRITIC, NoDiacritic::filter(self::NO_DIACRITIC));
    }

    public function testDoNotReplaceCharsGermanLocale()
    {
        $this->assertEquals(self::NO_DIACRITIC, NoDiacritic::filter(self::NO_DIACRITIC, 'de'));
    }

    public function testDoNotReplaceCharsDanishLocale()
    {
        $this->assertEquals(self::NO_DIACRITIC, NoDiacritic::filter(self::NO_DIACRITIC, 'da'));
    }

    public function testDefaultLocale()
    {
        $this->assertEquals("aaceeeeiioeouuAACEEEEIIOEaeinouuAEINOUUOUUaeiouAEIOUaAcCoOoOoOaaaedeoosthuyAAAEDEOOTHU",
            NoDiacritic::filter(self::DIACRITIC));
    }

    public function testGermanLocale()
    {
        $this->assertEquals("aaceeeeiioeouuAACEEEEIIOEaeinouueAEINOUUeOUUaeiouAEIOUaAcCoOoOoOaeaaedeoeossthueyAeAAEDEOeOTHUe",
            NoDiacritic::filter(self::DIACRITIC, "de"));
    }

    public function testDanishLocale()
    {
        $this->assertEquals("aaceeeeiioeouuAACEEEEIIOEaeinouuAEINOUUOUUaeiouAEIOUaAcCoOoOoOaaaaedeooesthuyAAaAeDEOOeTHU",
            NoDiacritic::filter(self::DIACRITIC, "da"));
    }
}