/**
 * Dong bo code tu host Windows vao container.
 *
 * Khi ban sua plugin trong D:\KLEER (hoac thu muc khac tren host), chay
 * script nay de dua code moi vao may chong Docker. Mac dinh dong bo
 * kleer-payments.
 *
 * Dung:  node tools/sync-to-docker.js [duong-dan-thu-muc-nguon]
 */

const { execFileSync } = require( 'child_process' );
const path = require( 'path' );
const fs = require( 'fs' );

const CONTAINER = 'shopdongho-wordpress-1';

// Thu muc dich trong container.
const TARGET = '/var/www/html/wp-content/plugins/kleer-payments';

const source = process.argv[ 2 ]
	? path.resolve( process.argv[ 2 ] )
	: path.resolve( __dirname, '..', 'wp-content', 'plugins', 'kleer-payments' );

if ( ! fs.existsSync( source ) ) {
	console.error( `Khong tim thay thu muc nguon: ${ source }` );
	process.exit( 1 );
}

console.log( `Nguon : ${ source }` );
console.log( `Dich  : ${ CONTAINER}:${ TARGET }` );

// Xoa ban cu trong container truoc, neu khong se con file da xoa o host.
execFileSync( 'docker', [ 'exec', CONTAINER, 'sh', '-c', `rm -rf "${ TARGET }" && mkdir -p "${ TARGET }"` ], {
	stdio: 'inherit',
} );

execFileSync( 'docker', [ 'cp', `${ source }/.`, `${ CONTAINER }:${ TARGET }` ], { stdio: 'inherit' } );

const count = execFileSync(
	'docker',
	[ 'exec', CONTAINER, 'sh', '-c', `find "${ TARGET }" -type f | wc -l` ],
	{ encoding: 'utf8' }
).trim();

console.log( `\nDa dong bo ${ count } file vao container.` );
console.log( 'Nho kiem tra lai bang cach tai lai trang trinh duyet.' );