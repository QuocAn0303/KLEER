/**
 * Ve ma QR VietQR tu payload chuoi nam trong thuoc tinh data-kleer-qr.
 *
 * Thu vien qrcode.min.js (Kazuhiko Arase, MIT) cung cap thuat toan ma hoa QR.
 * Ta dung ban SVG nen QR luon ro net ke ca tren may in nhieu, va khong can GD.
 */
( function () {
	'use strict';

	/**
	 * Muc bao loi cua EMVCo. Q cao hon dung de chu duoc chuoi QR dai.
	 */
	var ERROR_CORRECTION = 'M';

	/**
	 * Kich thuoc canh QR theo so pixel.
	 */
	var QR_SIZE = 240;

	/**
	 * Render mot phan tu chua payload.
	 *
	 * @param {HTMLElement} node Phan tu mang thuoc tinh data-kleer-qr.
	 */
	function renderQR( node ) {
		var payload = node.getAttribute( 'data-kleer-qr' );

		if ( ! payload ) {
			return;
		}

		// qrcode-generator dung bien global qrcode.
		if ( typeof window.qrcode !== 'function' ) {
			return;
		}

		try {
			// Tham so 0 = tu chon phien QR phu hop do dai chuoi.
			var qr = window.qrcode( 0, ERROR_CORRECTION );
			qr.addData( payload );
			qr.make();

			var svg = qr.createSvgTag( { cellSize: QR_SIZE, margin: 2, scalable: true } );
			node.innerHTML = svg;

			// Bo attribute chua payload de khong loi DOM canh bao HTML.
			node.removeAttribute( 'data-kleer-qr' );
			node.classList.add( 'kleer-vietqr__code--rendered' );
		} catch ( error ) {
			node.classList.add( 'kleer-vietqr__code--failed' );
		}
	}

	function renderAll() {
		var nodes = document.querySelectorAll( '[data-kleer-qr]' );

		Array.prototype.forEach.call( nodes, renderQR );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', renderAll );
	} else {
		renderAll();
	}
} )();