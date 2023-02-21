#!/usr/bin/perl -w

$debug =0 ;

$C_database_DBD = "mysql";
$C_database_host = "localhost";
$C_database = "SSCtest";
$C_database_userid = "root";
$C_database_password = "password";

#use strict;
#use Spreadsheet::ParseExcel;
use Spreadsheet::XLSX;
use POSIX qw(ceil floor);

die "You must provide a filename to $0 to be parsed as an Excel file" unless @ARGV;

&database_connect;

#my $excel = Spreadsheet::ParseExcel::Workbook->Parse($ARGV[0]);

use Text::Iconv;
my $converter = Text::Iconv -> new ("utf-8", "windows-1251");
my $excel = Spreadsheet::XLSX -> new ($ARGV[0]);


foreach my $sheet (@{$excel->{Worksheet}}) {
 printf("Sheet: %s\n", $sheet->{Name}) if $debug;
 $sheet->{MaxRow} ||= $sheet->{MinRow};
 for($row = $sheet->{MinRow}; defined $sheet->{MaxRow} && $row <= $sheet->{MaxRow}; $row++) {
  $sheet->{MaxCol} ||= $sheet->{MinCol};
  for($col = $sheet->{MinCol}; defined $sheet->{MaxCol} && $col <= $sheet->{MaxCol}; $col++) {

   my $cell = $sheet->{Cells}[$row][$col];
   if ($cell) {
    printf("( %s , %s ) => %s\n", $row, $col, $cell->{Val}) if $debug;


    # Table Number
    if ($cell->{Val} =~ /Table\s(.*?)\&\#10\;/) {
     $tbl = $1;
     print "*** This is for table: $tbl";
     if ($cell->{Val} =~ /20(\d{2})/) {
      $tblYear = "20$1";
      print " ($tblYear)";
     }
     print "\n";
     $row = $row+6;
     $col--;
    }

    if ($cell->{Val} =~ /(Transportation)/) {
       print " (found transportation)" if $debug;
       $rowName = getRowName($1);
       $inside = 1;
    }


    if ($inside) {
     for ($mycol = 1; $mycol <= 701; $mycol++) {
      my $Incell = $sheet->{Cells}[$row][$mycol];
      $value = sprintf("%1.2f", $Incell->{Val});

      $sql = "select * from ssc_standard_king_privatetransportation where id='$tbl' and famtype='$mycol'";
      $sth = $dbh->prepare($sql) or die "Can't prepare $sql: $dbh->errstr\n";
      $rv = $sth->execute or do{ print  "Content-type: text/html\n\nCan't execute ($sql)<p>$DBI::errstr\n";};
      $nr = $sth->rows;
      $sth->finish;

      if ($nr == 1) {
       $sql = "update ssc_standard_king_privatetransportation set $rowName='$value' where id='$tbl' and famtype='$mycol'\n";
      }
      else {
       $sql = "insert into ssc_standard_king_privatetransportation set id='$tbl',famtype='$mycol',$rowName='$value'\n";
      }
      $sth = $dbh->prepare($sql) or die "Can't prepare $statement: $dbh->errstr\n";
      $rv = $sth->execute or do{ print  "Content-type: text/html\n\nCan't execute ($sql)<p>$DBI::errstr\n";};

      print "$sql\n";
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
   if ( !defined $dbh ) { print "Cannot connect to mySQL server: $DBI::errstr\n"; exit; }
}

sub database_disconnect {
 $dbh->disconnect;
}

