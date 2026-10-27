<?php
// Run: php tests/test-csv-escaping.php
require __DIR__ . '/bootstrap.php';

require dirname( __DIR__ ) . '/includes/classes/class-submissions.php';

$s = new \paystack\payment_forms\Submissions();

$cases = array(
	array( '=1+1', '"\'=1+1"' ),
	array( '+cmd|x', '"\'+cmd|x"' ),
	array( '-cmd', '"\'-cmd"' ),
	array( '@SUM(A1)', '"\'@SUM(A1)"' ),
	array( '  =1+1', '"\'  =1+1"' ),
	array( "\t=1", "\"'\t=1\"" ),
	array( "\r=1", "\"'\r=1\"" ),
	array( "\n=1", "\"'\n=1\"" ),
	array( '+234 800 000 0000', '"+234 800 000 0000"' ),
	array( '08012345678', '"08012345678"' ),
	array( '(080) 123-4567', '"(080) 123-4567"' ),
	array( '-2+3', '"\'-2+3"' ),
	array( 'say "hi"', '"say ""hi"""' ),
	array( '=" x"', '"\'="" x"""' ),
	array( 'plain', '"plain"' ),
	array( '', '""' ),
	array( 5, '"5"' ),
	array( -5, '"\'-5"' ),
	array( 1.5, '"1.5"' ),
	array( true, '"1"' ),
	array( array( 'a' => 1 ), '"{""a"":1}"' ),
	array( array( '=x' ), '"[""=x""]"' ),
);

define( 'EXPECTED', 23 );

foreach ( $cases as $c ) {
	check( var_export( $c[0], true ), $c[1], $s->prep_csv_data( $c[0] ) );
}

check( 'null is an empty quoted cell', '""', $s->prep_csv_data( null ) );

echo "$total assertions, $failures failures\n";
$completed = true;
exit( ( $failures > 0 || EXPECTED !== $total ) ? 1 : 0 );
