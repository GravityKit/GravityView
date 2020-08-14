/**
 * @global jQuery
 * @global gvSupportData
 * @global gvSupportTranslation
 */
!function(e,t,n){function a(){var e=t.getElementsByTagName("script")[0],n=t.createElement("script");n.type="text/javascript",n.async=!0,n.src="https://beacon-v2.helpscout.net",e.parentNode.insertBefore(n,e)}if(e.Beacon=n=function(t,n,a){e.Beacon.readyQueue.push({method:t,options:n,data:a})},n.readyQueue=[],"complete"===t.readyState)return a();e.attachEvent?e.attachEvent("onload",a):e.addEventListener("load",a,!1)}(window,document,window.Beacon||function(){});

window.Beacon('init', 'b4f6255a-91bc-436c-a5a2-4cca051ad00f');

window.Beacon( "config", {
	color: '#4d9bbe',
	display: {
		style: 'iconAndText',
		text: gvSupport.translation.needHelp,
		iconImage: 'buoy',
	},
	poweredBy: false,
	docsEnabled: true,
	messagingEnabled: ( 1 === gvSupport.contactEnabled * 1 ),
	topArticles: true,
	zIndex: ( 10000 + 10 ) // Above #adminmenuwrap, which is 9990 and modal content, which is 10000 + 1
});

Beacon("identify", gvSupport.data );

if ( gvSupport.suggest.length ) {
	Beacon( "suggest", gvSupport.suggest );
}

Beacon( 'on', 'article-viewed',function() {
	document.querySelectorAll('.ui-tooltip').forEach(function(el) {
		el.style.display = 'none';
	});
});
