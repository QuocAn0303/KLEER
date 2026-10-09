/**
 * Kiem chung QR thuc su render duoc o phia trinh duyet.
 *
 * Nap thu vien qrcode-generator vao mot vm context (ban trinh duyet khong
 * co document that), nap payload cua don that, va kiem tra ma QR duoc dung.
 *
 * Chay: node tests/test-qr-render.js
 */

const fs = require( 'fs' );
const path = require( 'path' );
const vm = require( 'vm' );

// Payload that sinh ra tu don BACS #243: 4.770.000 VND, VietinBank, ma don 243.
const PAYLOAD =
	'0002010102122639001097043600000106970436020402080303000520459925303704540747700005802VN' +
	'5905KLEER6006HA NOI6207010324363045EAB';

let pass = 0;
let fail = 0;

function check( label, actual, expected ) {
	if ( actual === expected ) {
		pass++;
		console.log( `  [PASS] ${ label }` );
		return;
	}
	fail++;
	console.log( `  [FAIL] ${ label }` );
	console.log( `         expected: ${ expected }` );
	console.log( `         actual:   ${ actual }` );
}

// qrcode-generator la ban trinh duyet, gan vao global window.
const libPath = path.join( __dirname, '..', 'assets', 'qrcode.min.js' );
const context = {
	window: {},
	document: {
		createElement: () => ( { setAttribute() {}, appendChild() {}, style: {} } ),
		createElementNS: () => ( { setAttribute() {}, appendChild() {} } ),
	},
};
context.self = context.window;
vm.createContext( context );
vm.runInContext( fs.readFileSync( libPath, 'utf8' ), context );

const qrcode = context.window.qrcode || context.qrcode;

console.log( '\n=== 1. Thu vien nap duoc ===' );
check( 'qrcode() la ham', typeof qrcode, 'function' );

console.log( '\n=== 2. Payload don that tao duoc QR ===' );
const qr = qrcode( 0, 'M' ); // 0 = tu chon phien QR theo do dai chuoi
qr.addData( PAYLOAD );
qr.make();

const modules = qr.getModuleCount();
console.log( `  so module: ${ modules }` );
check( 'QR la ma 21x21 tro len (phien >= 1)', modules >= 21, true );
check( 'so module la le (cong 4x4)', modules % 2, 1 );

console.log( '\n=== 3. Sinh duoc SVG de hien thi ===' );
const svg = qr.createSvgTag( { cellSize: 8, margin: 2, scalable: true } );
check( 'SVG co the', svg.startsWith( '<svg' ), true );
check( 'SVG co du kich thuoc', svg.length > 500, true );
console.log( `  do dai SVG: ${ svg.length } bytes` );

// Thu vien ve tung module bang mot subpath "M...l...z" trong mot <path> duy nhat,
// nen SVG nho gon hon nhieu so so voi cach ve bang <rect>.
const pathData = svg.match( / d="([^"]+)"/ );
const modulesDrawn = pathData ? ( pathData[ 1 ].match( /z/g ) || [] ).length : 0;
console.log( `  module ve trong path: ${ modulesDrawn }` );
check( 'SVG co path ve du lieu QR', !! pathData, true );
check( 'path ve du so module', modulesDrawn > 100, true );
check( 'viewBox khop so module', svg.includes( `viewBox="0 0 ${ modules * 8 + 4 } ${ modules * 8 + 4 }"` ), true );

console.log( '\n=== 4. Cac phuong thuc doc ma ===' );
check( 'getModuleCount()', typeof modules, 'number' );
check( 'isDark() tra boolean', typeof qr.isDark( 0, 0 ), 'boolean' );

// Neu chuoi qua dai, thu vien phai bao loi chu khong duoc im lang bo qua.
console.log( '\n=== 5. Chuoi qua dai phai bi tu choi ===' );
let threw = false;
try {
	const tooLong = qrcode( 0, 'M' );
	tooLong.addData( 'x'.repeat( 8000 ) );
	tooLong.make();
} catch ( e ) {
	threw = true;
}
check( 'chuoi 8000 ky tu bi tu choi', threw, true );

console.log( `\n===============================\nPASS: ${ pass }   FAIL: ${ fail }\n===============================\n` );

process.exit( fail > 0 ? 1 : 0 );