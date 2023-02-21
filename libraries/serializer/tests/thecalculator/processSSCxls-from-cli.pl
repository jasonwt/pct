#!/usr/bin/perl -w

#$debug = 1;

$C_database_DBD = "mysql";
$C_database_host = "localhost";
$C_database = "SSCdev";
$C_database_userid = "ssc";
$C_database_password = "red32dog";

use Spreadsheet::XLSX;
use POSIX qw(ceil floor);
use Text::Iconv;
my $converter = Text::Iconv -> new ("utf-8", "windows-1251");

die "You must provide a filename to $0 to be parsed as an Excel file" unless @ARGV;

&database_connect;

my $excel = Spreadsheet::XLSX -> new ($ARGV[0], $converter);

foreach my $sheet (@{$excel->{Worksheet}}) {

    printf("Sheet: %s\n", $sheet->{Name}) if $debug;

    $sheet->{MaxRow} ||= $sheet->{MinRow};
    foreach my $row ($sheet -> {MinRow} .. $sheet -> {MaxRow}) {
    $sheet->{MaxCol} ||= $sheet->{MinCol};

        foreach my $col ($sheet -> {MinCol} ..  $sheet -> {MaxCol}) {

            my $cell = $sheet->{Cells}[$row][$col];

            if ($cell) {
                printf("( %s , %s ) => %s\n", $row, $col, $cell->{Val}) if $debug;


                # Table Number
                if ($cell->{Val} =~ /Table\s(.*?)\n/) {
                    $tbl = $1;
                    $tbl =~ s/\r//g;

                    print "*** This is for table: $tbl";
                    if ($cell->{Val} =~ /20(\d{2})/) {
                        $tblYear = "20$1";
                        print " ($tblYear)";
                    }
                    
                    print "\n";
                    $row = $row+6;
                    $col--;
                }

                if ($cell->{Val} =~ /^\s?(Housing)\s?$/) {
                    $rowName = getRowName($1);
                    $inside = 1;
                }

                if ($cell->{Val} =~ /^\s?(Child Care)\s?$/) {
                    $rowName = getRowName($1);
                    $inside = 1;
                }

                if ($cell->{Val} =~ /^\s?(Food)\s?$/) {
                    $rowName = getRowName($1);
                    $inside = 1;
                }

                if ($cell->{Val} =~ /^\s?(Transportation)\s?$/) {
                    $rowName = getRowName($1);
                    $inside = 1;
                }

                if ($cell->{Val} =~ /^\s?(Health Care)\s?$/) {
                    $rowName = getRowName($1);
                    $inside = 1;
                }

                if ($cell->{Val} =~ /^\s?(Miscellaneous)\s?$/) {
                    $rowName = getRowName($1);
                    $inside = 1;
                }

                if ($cell->{Val} =~ /^\s?(Taxes)\s?$/) {
                    $rowName = getRowName($1);
                    $inside = 1;
                }

                if ($cell->{Val} =~ /^\s?(Earned Income)\s?/) {
                    $rowName = getRowName("eitc");
                    $inside = 1;
                }

                if ($cell->{Val} =~ /^\s?(Child Care Tax Credit)\s?/) {
                    $rowName = getRowName("cctc");
                    $inside = 1;
                }

                if ($cell->{Val} =~ /^\s?(Child Tax Credit)\s?/) {
                    $rowName = getRowName("ctc");
                    $inside = 1;
                }

                if ($cell->{Val} =~ /(Hourly)/) {
                    $rowName = getRowName("SSChourly");
                    $inside = 1;
                }

                if ($cell->{Val} =~ /(Monthly)/) {
                    $rowName = getRowName("SSCmonthly");
                    $inside = 1;
                }

                if ($cell->{Val} =~ /(Annual)/) {
                    $rowName = getRowName("SSCannually");
                    $inside = 1;
                }


                if ($inside) {
                    for ($mycol = 1; $mycol <= 720; $mycol++) {
                        my $Incell = $sheet->{Cells}[$row][$mycol];
                        $value = sprintf("%1.2f", $Incell->{Val});

                        $sql = "select * from ssc_standard where id='$tbl' and sscyear='$tblYear' and famtype='$mycol'";
                        $sth = $dbh->prepare($sql) or die "Can't prepare $statement: $dbh->errstr\n";
                        $rv = $sth->execute or do { 
                            print  "Content-type: text/html\n\nCan't execute ($sql)<p>$DBI::errstr\n";
                        };
                        
                        $nr = $sth->rows;
                        $sth->finish;

                        if ($nr == 1) {
                            $sql2 = "update ssc_standard set $rowName='$value' where id='$tbl' and sscyear='$tblYear' and famtype='$mycol';\n";
                        } else {
                            $sql2 = 'insert into ssc_standard set id=\''.$tbl/\'';
                            #$sql2 .= ",sscyear=$tblYear,famtype='$mycol',$rowName='$value';\n";
                        }

                        #      print $sql2;
                    }

                    $row++;
                    $col--;
                    $inside = 0;
                }
            }
        }
    }
}

&database_disconnect;

sub getRowName {   
    my $name=shift;
    $name = lc($name);
    $name =~ s/\s//g;
    return $name;
}

sub database_connect {
    use DBI;
    $dbh = DBI->connect("DBI:$C_database_DBD:$C_database:$C_database_host", $C_database_userid,$C_database_password);
    if ( !defined $dbh ) { 
        print "Cannot connect to mySQL server: $DBI::errstr\n"; 
        exit; 
    }
}

sub database_disconnect {
    $dbh->disconnect;
}

