#!/usr/bin/perl5.8.8

$|=1; #clears the buffer stack

use CGI::Carp qw(fatalsToBrowser);
use CGI qw(:standard);
use Spreadsheet::ParseExcel;
use File::Type;

my $cgi = new CGI;
print $cgi->header;

$PATH = "C:/thecalculator";

$debug = 0;

##
# TODO:
# maybe do ajax or something with a progress bar
# 
#
# Not very adaptive. Assumes that xls is in format
# currently employed (2006). If there are any changes in
# the xls format of the SSC, then this program will 
# have to be updated to accommodate.

### if hosted on Unix, use...
#$C_database_DBD = "Sybase"; # MSSQL is Sybase, so this is correct.
#$C_database_host = "server=NTWebSQL";

### if hosted on NT, use...
$C_database_DBD = "ODBC"; # use ODBC to connect from NT, must have a System DSN named 'ssc'
$C_database_host = "ssc";

$C_database = "SSC";
$C_database_userid = "wassc";
$C_database_password = 'P@ssw0rd';

my $ft = new File::Type;

if ($cgi->param('submit')) {
    $filename = $cgi->param('uploaded_file');
    $xlsfile = $cgi->tmpFileName($filename);
    $filetype = $ft->checktype_filename($xlsfile);
   #print $cgi->header; print $filename; print $filetype; #exit;
    if (!$xlsfile) {
        $error = "You must provide an Excel (.xls) file to be parsed.<br/>";
        &showForm;
        exit;
    }
}
else {
    &showForm;
    exit;	
}

&database_connect;

my $excel = Spreadsheet::ParseExcel::Workbook->Parse($xlsfile);
$record_cnt = 1;

foreach my $sheet (@{$excel->{Worksheet}}) {
    printf("Sheet: %s\n", $sheet->{Name}) if $debug;
    $sheet->{MaxRow} ||= $sheet->{MinRow};
    for($row = $sheet->{MinRow}; defined $sheet->{MaxRow} && $row <= $sheet->{MaxRow}; $row++) {
        $sheet->{MaxCol} ||= $sheet->{MinCol};
        for($col = $sheet->{MinCol}; defined $sheet->{MaxCol} && $col <= $sheet->{MaxCol}; $col++) {
            my $cell = $sheet->{Cells}[$row][$col];
            if ($cell) {
               # printf("( %s , %s ) => %s\n", $row, $col, $cell->{Val}) if $debug;

                # Table Number
                if ($cell->{Val} =~ /Table\s(.*?)\W/) {
                    $tbl = $1;
                    print "*** This is for table: $tbl" if $debug;
                    if ($cell->{Val} =~ /20(\d{2})/) {
                        $tblYear = "20$1";
                        print " ($tblYear)" if $debug;
                    }
                    print "<br/>\n" if $debug;
                    $row = $row+6;
                    $col--;
                }



                if ($cell->{Val} =~ /(Transportation)/) {
                	print " (found transportation)" if $debug;
                    $rowName = getRowName($1);
                    $inside = 1;
                }


  
                if ($inside) {
                    for ($mycol = 1; $mycol <= 70; $mycol++) {
                        my $Incell = $sheet->{Cells}[$row][$mycol];
                        $value = sprintf("%1.2f", $Incell->{Val});

                        $sql = "select * from ssc_standard_king_privatetransportation where locale='$tbl' and famtype='$mycol'";
                        $sth = $dbh->prepare($sql) or die "Can't prepare $statement: $dbh->errstr\n";
                        $rv = $sth->execute or do{ print  "Content-type: text/html\n\nCan't execute ($sql)<p>$DBI::errstr\n";};
                        while (@r = $sth->fetchrow_array()) {
                            # do nothing really, but $sth->rows doesn't work correctly with MSSQL without it.
                        }
                        $nr = $sth->rows;
                        $sth->finish;

                        if ($nr == 1) {
                            $sql = "update ssc_standard_king_privatetransportation set $rowName='$value' where locale='$tbl' and famtype='$mycol'\n";
                        }
                        else {
                            $sql = "insert into ssc_standard_king_privatetransportation (locale,famtype,$rowName) values ('$tbl','$mycol','$value')";
                            $total_number_rows++;
                        }
                        $sth = $dbh->prepare($sql) or die "Can't prepare $statement: $dbh->errstr\n";
                        unless ($debug) {$rv = $sth->execute or do{ print  "Content-type: text/html\n\nCan't execute ($sql)<p>$DBI::errstr\n";};}
                        print "$sql<br/>\n" if $debug;
                        $record_cnt++;
                    }
                    $row++;
                    $col--;
                    $inside = 0;
                }    
            }
        }
    }
}
#$dbh->commit; # think we need this with mssql using DBD::Sybase ..nope, we don't.
&database_disconnect;
&showFinished;
exit;


sub getRowName {   
    my $name=shift;
    $name = lc($name);
    $name =~ s/\s//g;
    return $name;
}

sub showFinished {
    include("$PATH/templates/admin/index/header.inc");
    include("$PATH/templates/admin/index/perl_nav.inc");
    print <<"EOT";
     <div id="adminContent">
     <div class="adminHeader">SSC ssc_standard_king_privatetransportation Upload</div>

      XLS File uploaded $total_number_rows rows with $record_cnt fields processed.
          
     </div>
EOT
    include("$PATH/templates/admin/index/footer.inc");	
}

sub showForm {
    include("$PATH/templates/admin/index/header.inc");
    include("$PATH/templates/admin/index/perl_nav.inc");
    print <<"EOT";
     <div id="adminContent">
     <div class="adminHeader">SSC ssc_standard_king_privatetransportation Upload</div>

     <div id="uploadForm">
     <form enctype="multipart/form-data" action="" method="post">
     Please select a file to upload: <br/> <br/>
     <input type="file" name="uploaded_file">
     <div>
     <input type="submit" name="submit" value="Upload XLS File" class="inputGeneralButton" onclick="return showProcessingUpload();"/>
     </div>
     </form>
	 Once you click upload, please wait for the program to finish. This may take several minutes.         
     </div>
     <div id="uploadProcessing" style="display: none;">
     Processing, please wait... <img src="/i/ajax-loader.gif" border="0">
     </div>
     </div>
EOT
    include("$PATH/templates/admin/index/footer.inc");	
}

sub include() {
    my $file = shift;
    open(FILE, $file) || die("$! :: $file");
    while(<FILE>) {
 	    print $_;
    }
    close(FILE);
}
	
sub database_connect {
    use DBI;
    $dbh = DBI->connect("DBI:$C_database_DBD:$C_database_host", $C_database_userid,$C_database_password);
        if ( !defined $dbh ) { print $cgi->header; print "Cannot connect to msSQL server: $DBI::errstr\n"; exit; }
    $dbh->do("use $C_database");
}

sub database_disconnect {
    $dbh->disconnect;
}
