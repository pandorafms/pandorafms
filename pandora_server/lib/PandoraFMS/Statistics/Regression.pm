################################################################
# Statistics::Regression package included in Pandora FMS.
# See: https://metacpan.org/pod/Statistics::Regression
################################################################
package PandoraFMS::Statistics::Regression;

$VERSION = '0.53';
my $DATE = "2007/07/07";
my $MNAME= "$0::Statistics::Regression";

use strict;
use warnings FATAL => qw{ uninitialized };

use Carp;

################################################################
=pod

=head1 NAME

  Regression.pm - weighted linear regression package (line+plane fitting)


=head1 SYNOPSIS

  use Statistics::Regression;

  # Create regression object
  my $reg = Statistics::Regression->new( "sample regression", [ "const", "someX", "someY" ] );

  # Add data points
  $reg->include( 2.0, [ 1.0, 3.0, -1.0 ] );
  $reg->include( 1.0, [ 1.0, 5.0, 2.0 ] );
  $reg->include( 20.0, [ 1.0, 31.0, 0.0 ] );
  $reg->include( 15.0, [ 1.0, 11.0, 2.0 ] );

or

  my %d;
  $d{const} = 1.0; $d{someX}= 5.0; $d{someY}= 2.0; $d{ignored}="anything else";
  $reg->include( 3.0, \%d );  # names are picked off the Regression specification

Please note that *you* must provide the constant if you want one.

  # Finally, print the result
  $reg->print();

This prints the following:

  ****************************************************************
  Regression 'sample regression'
  ****************************************************************
  Name           	       Theta	      StdErr	 T-stat
  [0='const']    	      0.2950	      6.0512	   0.05
  [1='someX']    	      0.6723	      0.3278	   2.05
  [2='someY']    	      1.0688	      2.7954	   0.38

  R^2= 0.808, N= 4
  ****************************************************************



The hash input method has the advantage that you can now just
fill the observation hashes with all your variables, and use the
same code to run regression, changing the regression
specification at one and only one spot (the new() invokation).
You do not need to change the inputs in the include() statement.
For example,

  my @obs;  ## a global variable.  observations are like: %oneobs= %{$obs[1]};

  sub run_regression {
    my $reg = Statistics::Regression->new( $_[0], $_[2] );
    foreach my $obshashptr (@obs) { $reg->include( $_[1], $_[3] ); }
    $reg->print();
  }

  run_regression("bivariate regression",  $obshashptr->{someY}, [ "const", "someX" ] );
  run_regression("trivariate regression",  $obshashptr->{someY}, [ "const", "someX", "someZ" ] );



Of course, you can use the subroutines to do the printing work yourself:

  my @theta  = $reg->theta();
  my @se     = $reg->standarderrors();
  my $rsq    = $reg->rsq();
  my $adjrsq = $reg->adjrsq();
  my $ybar   = $reg->ybar();  ## the average of the y vector
  my $sst    = $reg->sst();  ## the sum-squares-total
  my $sigmasq= $reg->sigmasq();  ## the variance of the residual
  my $k      = $reg->k();   ## the number of variables
  my $n      = $reg->n();   ## the number of observations

In addition, there are some other helper routines, and a
subroutine linearcombination_variance().  If you don't know what
this is, don't use it.


=head1 BACKGROUND WARNING

You should have an understanding of OLS regressions if you want
to use this package.  You can get this from an introductory
college econometrics class and/or from most intermediate college
statistics classes.  If you do not have this background
knowledge, then this package will remain a mystery to you.
There is no support for this package--please don't expect any.


=head1 DESCRIPTION

Regression.pm is a multivariate linear regression package.  That
is, it estimates the c coefficients for a line-fit of the type

  y= c(0)*x(0) + c(1)*x1 + c(2)*x2 + ... + c(k)*xk

given a data set of N observations, each with k independent x
variables and one y variable.  Naturally, N must be greater than
k---and preferably considerably greater.  Any reasonable
undergraduate statistics book will explain what a regression is.
Most of the time, the user will provide a constant ('1') as x(0)
for each observation in order to allow the regression package to
fit an intercept.


=head1 ALGORITHM

=head2 Original Algorithm (ALGOL-60):

	W.  M.  Gentleman, University of Waterloo, "Basic
	Description For Large, Sparse Or Weighted Linear Least
	Squares Problems (Algorithm AS 75)," Applied Statistics
	(1974) Vol 23; No. 3

Gentleman's algorithm is I<the> statistical standard. Insertion
of a new observation can be done one observation at any time
(WITH A WEIGHT!), and still only takes a low quadratic time.
The storage space requirement is of quadratic order (in the
indep variables). A practically infinite number of observations
can easily be processed!

=head2 Internal Data Structures

R=Rbar is an upperright triangular matrix, kept in normalized
form with implicit 1's on the diagonal.  D is a diagonal scaling
matrix.  These correspond to "standard Regression usage" as

                X' X  = R' D R

A backsubsitution routine (in thetacov) allows to invert the R
matrix (the inverse is upper-right triangular, too!). Call this
matrix H, that is H=R^(-1).

	  (X' X)^(-1) = [(R' D^(1/2)') (D^(1/2) R)]^(-1)
	  = [ R^-1 D^(-1/2) ] [ R^-1 D^(-1/2) ]'


=head1 BUGS/PROBLEMS

None known.

=over 4

=item Perl Problem

Unfortunately, perl is unaware of IEEE number representations.
This makes it a pain to test whether an observation contains any
missing variables (coded as 'NaN' in Regression.pm).

=back

=for comment
pod2html -noindex -title "perl weighted least squares regression package" Regression.pm > Regression.html


=head1 VERSION and RECENT CHANGES

2007/04/04:  Added Coefficient Standard Errors

2007/07/01:  Added self-test use (if invoked as perl Regression.pm)
	     at the end.  cleaned up some print sprintf.
             changed syntax on new() to eliminate passing K.

2007/07/07:  allowed passing hash with names to include().


=head1 AUTHOR

Naturally, Gentleman invented this algorithm.  It was adaptated
by Ivo Welch.  Alan Miller (alan\@dmsmelb.mel.dms.CSIRO.AU)
pointed out nicer ways to compute the R^2. Ivan Tubert-Brohman
helped wrap the module as as a standard CPAN distribution.

=head1 LICENSE

This module is released for free public use under a GPL license.

(C) Ivo Welch, 2001,2004, 2007.

=cut


################################################################
#### let's start with handling of missing data ("nan" or "NaN")
################################################################
use constant TINY => 1e-8;
my $nan= "NaN";

sub isNaN { 
  if ($_[0] !~ /[0-9nan]/) { confess "$MNAME:isNaN: definitely not a number in NaN: '$_[0]'"; }
  return ($_[0]=~ /NaN/i) || ($_[0] != $_[0]);
}


################################################################
### my $reg = Statistics::Regression->new($regname, \@var_names)
###
### Receives the number of variables on each observations (i.e.,
### an integer) and returns the blessed data structure as a
### Statistics::Regression object. Also takes an optional name
### for this regression to remember, as well as a reference to a
### k*1 array of names for the X coefficients.
###
### I have now made it mandatory to give some names.
###
################################################################
sub new {
  my $classname= shift;  (!ref($classname)) or confess "$MNAME:new: bad class call to new ($classname).\n";
  my $regname= shift || "no-name";
  my $xnameptr= shift;

  (defined($regname)) or confess "$MNAME:new: bad name in for regression.  no undef allowed.\n";
  (!ref($regname)) or confess "$MNAME:new: bad name in for regression.\n";
  (defined($xnameptr)) or confess "$MNAME:new: You must provide variable names, because this tells me the number of columns.  no undef allowed.\n";
  (ref($xnameptr) eq "ARRAY") or confess "$MNAME:new: bad xnames for regression. Must be pointer.\n";

  my $K= (@{$xnameptr});

  if (!defined($K)) { confess "$MNAME:new: cannot determine the number of variables"; }
  if ($K<=1) { confess "$MNAME:new: Cannot run a regression without at least two variables."; }

  sub zerovec {
    my @rv;
    for (my $i=0; $i<=$_[0]; ++$i) { $rv[$i]=0; } 
    return \@rv;
  }

  bless {
	 k => $K,
	 regname => $regname,
	 xnames => $xnameptr,

	 # constantly updated
	 n => 0,
	 sse => 0,
	 syy => 0,
	 sy => 0,
	 wghtn => 0,
	 d => zerovec($K),
	 thetabar => zerovec($K),
	 rbarsize => ($K+1)*$K/2+1,
	 rbar => zerovec(($K+1)*$K/2+1),

	 # other constants
	 neverabort => 0,

	 # computed on demand
	 theta => undef,
	 sigmasq => undef,
	 rsq => undef,
	 adjrsq => undef
	}, $classname;
}


################################################################
### $reg->include( $y, [ $x1, $x2, $x3 ... $xk ], $weight );
###
### Add one new observation. The weight is optional. Note that
### inclusion with a weight of -1 can be used to delete an
### observation.
###
### The error checking and transfer of arguments is clutzy, but
### works.  if I had POSIX assured, I could do better number
### checking.  right now, I don't do any.
###
### Returns the number of observations so far included.
################################################################
sub include {
  my $this = shift;
  my $yelement= shift;
  my $xin= shift;
  my $weight= shift || 1.0;

  # modest input checking;
  (ref($this)) or confess "$MNAME:include: bad class call to include.\n";
  (defined($yelement)) or confess "$MNAME:include: bad call for y to include.  no undef allowed.\n";
  (!ref($yelement)) or confess "$MNAME:include: bad call for y to include.  need scalar.\n";
  (defined($xin)) or confess "$MNAME:include: bad call for x to include.  no undef allowed.\n";
  (ref($xin)) or confess "$MNAME:include: bad call for x to include. need reference.\n";
  (!ref($weight)) or confess "$MNAME:include: bad call for weight to include. need scalar.\n";


  # omit observations with missing observations;
  (defined($yelement)) or confess "$MNAME:include: you must give a y value (predictor).";
  (isNaN($yelement)) and return $this->{n};  # ignore this observation;
  ## should check for number, not string


  # check and transfer the X vector
  my @xrow;
  if (ref($xin) eq "ARRAY") { @xrow= @{$xin}; }
  else {
    my $xctr=0;
    foreach my $nm (@{$this->{xnames}}) {
      (defined($xin->{$nm})) or confess "$MNAME:include: Variable '$nm' needs to be set in hash.\n";
      $xrow[$xctr]= $xin->{$nm};
      ++$xctr;
    }
  }

  my @xcopy;
  for (my $i=1; $i<=$this->{k}; ++$i) { 
    (defined($xrow[$i-1]))
      or confess "$MNAME:include: Internal Error: at N=".($this->{n}).", the x[".($i-1)."] is undef.  use NaN for missing.";
    (isNaN($xrow[$i-1])) and return $this->{n};
    $xcopy[$i]= $xrow[$i-1];
    ## should check for number, not string
  }

  ################ now comes the real routine

  $this->{syy}+= ($weight*($yelement*$yelement));
  $this->{sy}+= ($weight*($yelement));
  if ($weight>=0.0) { ++$this->{n}; } else { --$this->{n}; }

  $this->{wghtn}+= $weight;

  for (my $i=1; $i<=$this->{k};++$i) {
    if ($weight==0.0) { return $this->{n}; }
    if (abs($xcopy[$i])>(TINY)) {
      my $xi=$xcopy[$i];

      my $di=$this->{d}->[$i];
      my $dprimei=$di+$weight*($xi*$xi);
      my $cbar= $di/$dprimei;
      my $sbar= $weight*$xi/$dprimei;
      $weight*=($cbar);
      $this->{d}->[$i]=$dprimei;
      my $nextr=int( (($i-1)*( (2.0*$this->{k}-$i))/2.0+1) );
      if (!($nextr<=$this->{rbarsize}) ) { confess "$MNAME:include: Internal Error 2"; }
      my $xk;
      for (my $kc=$i+1;$kc<=$this->{k};++$kc) {
	$xk=$xcopy[$kc]; $xcopy[$kc]=$xk-$xi*$this->{rbar}->[$nextr];
	$this->{rbar}->[$nextr]= $cbar * $this->{rbar}->[$nextr]+$sbar*$xk;
	++$nextr;
      }
      $xk=$yelement; $yelement-= $xi*$this->{thetabar}->[$i];
      $this->{thetabar}->[$i]= $cbar*$this->{thetabar}->[$i]+$sbar*$xk;
    }
  }
  $this->{sse}+=$weight*($yelement*$yelement);

  # indicate that Theta is garbage now
  $this->{theta}= undef;
  $this->{sigmasq}= undef; $this->{rsq}= undef; $this->{adjrsq}= undef;

  return $this->{n};
}


################################################################
###
### $reg->rsq(), $reg->adjrsq(), $reg->sigmasq(), $reg->ybar(),
### $reg->sst(), $reg->k(), $reg->n()
###
### These methods provide common auxiliary information.  rsq,
### adjrsq, sigmasq, sst, and ybar have not been checked but are
### likely correct.  The results are stored for later usage,
### although this is somewhat unnecessary because the
### computation is so simple anyway.
################################################################

sub rsq {
  my $this= shift;
  return $this->{rsq}= 1.0- $this->{sse} / $this->sst();
}

sub adjrsq {
  my $this= shift;
  return $this->{adjrsq}= 1.0- (1.0- $this->rsq())*($this->{n}-1)/($this->{n} - $this->{k});
}

sub sigmasq {
  my $this= shift;
  return $this->{sigmasq}= ($this->{n}<=$this->{k}) ? "Inf" : ($this->{sse}/($this->{n} - $this->{k}));
}

sub ybar {
  my $this= shift;
  return $this->{ybar}= $this->{sy}/$this->{wghtn};
}

sub sst {
  my $this= shift;
  return $this->{sst}= ($this->{syy} - $this->{wghtn}*($this->ybar())**2);
}

sub k {
  my $this= shift;
  return $this->{k};
}
sub n {
  my $this= shift;
  return $this->{n};
}



################################################################
###  $reg->print()  [no arguments!]
###
### prints the estimated coefficients, and R^2 and N. For an
### example see the Synopsis.
################################################################
sub print {
  my $this= shift;

  print "****************************************************************\n";
  print "Regression '$this->{regname}'\n";
  print "****************************************************************\n";

  my $theta= $this->theta();
  my @standarderrors= $this->standarderrors();

  printf "%-15s\t%12s\t%12s\t%7s\n", "Name", "Theta", "StdErr", "T-stat";
  for (my $i=0; $i< $this->k(); ++$i) {
    my $name= "[$i".(defined($this->{xnames}->[$i]) ? "='$this->{xnames}->[$i]'":"")."]";
    printf "%-15s\t", $name;
    printf "%12.4f\t", $theta->[$i];
    printf "%12.4f\t", $standarderrors[$i];
    printf "%7.2f", ($theta->[$i]/$standarderrors[$i]);
    printf "\n";
  }

  print "\nR^2= ".sprintf("%.3f", $this->rsq()).", N= ".$this->n().", K= ".$this->k()."\n";
  print "****************************************************************\n";
}



################################################################
### $theta = $reg->theta or @theta = $reg->theta
###
### This is the work horse.  It estimates and returns the vector
### of coefficients. In scalar context returns an array
### reference; in list context it returns the list of
### coefficients.
################################################################
sub theta {
  my $this= shift;

  if (defined($this->{theta})) { 
    return wantarray ? @{$this->{theta}} : $this->{theta}; 
  }

  if ($this->{n} < $this->{k}) { return; }
  for (my $i=($this->{k}); $i>=1; --$i) {
    $this->{theta}->[$i]= $this->{thetabar}->[$i];
    my $nextr= int (($i-1)*((2.0*$this->{k}-$i))/2.0+1);
    if (!($nextr<=$this->{rbarsize})) { confess "$MNAME:theta: Internal Error 3"; }
    for (my $kc=$i+1;$kc<=$this->{k};++$kc) {
      $this->{theta}->[$i]-=($this->{rbar}->[$nextr]*$this->{theta}->[$kc]);
      ++$nextr;
    }
  }


  my $ref = $this->{theta}; shift(@$ref); # we are counting from 0

  # if in a scalar context, otherwise please return the array directly
  wantarray ? @{$this->{theta}} : $this->{theta};
}

################################################################
### @se= $reg->standarderrors()
###
### This is the most difficult routine.  Take it on faith.
###
###  R=Rbar is an upperright triangular matrix, kept in normalized
###  form with implicit 1's on the diagonal.  D is a diagonal scaling
###  matrix.  These correspond to "standard Regression usage" as
###
###                X' X  = R' D R
###
###  A backsubsitution routine (in thetacov) allows to invert the R
###  matrix (the inverse is upper-right triangular, too!). Call this
###  matrix H, that is H=R^(-1).
###
###	  (X' X)^(-1) = [(R' D^(1/2)') (D^(1/2) R)]^(-1)
###	  = [ R^-1 D^(-1/2) ] [ R^-1 D^(-1/2) ]'
###
###  Let's work this for our example, where
###
###  $reg->include( 2.0, [ 1.0, 3.0, -1.0 ] );
###  $reg->include( 1.0, [ 1.0, 5.0, 2.0 ] );
###  $reg->include( 20.0, [ 1.0, 31.0, 0.0 ] );
###  $reg->include( 15.0, [ 1.0, 11.0, 2.0 ] );
###
###  For debuggin, the X'X matrix for our example is
###	4, 50, 3
###	50 1116 29
###	3 29 9
###
###  Its inverse is
###	 0.70967 -0.027992 -0.146360
###	-0.02799  0.002082  0.002622
###	-0.14636  0.002622  0.151450
###
###  Internally, this is kept as follows
###
###  R is 1, 0, 0
###       12.5 1 0
###       0.75 -0.0173 1
###
###  d is the diagonal(4,491,6.603) matrix, which as 1/sqrt becomes dhi= 0.5, 0.04513, 0.3892
###
###  R * d * R' is indeed the X' X matrix.
###
###  The inverse of R is
###
###  1, 0, 0
###  -12.5 1 0
###  -0.9664 0.01731 1
###
###  in R, t(solve(R) %*% dhi) %*% t( t(solve(R) %*% dhi) ) is the correct inverse.
###
### The routine has a debug switch which makes it come out very verbose.
################################################################
my $debug=0;

sub standarderrors {
  my $this= shift;
  our $K= $this->{k};  # convenience

  our @u;
  sub ui {
    if ($debug) {
      ($_[0]<1)||($_[0]>$K) and confess "$MNAME:standarderrors: bad index 0 $_[0]\n";
      ($_[1]<1)||($_[1]>$K) and confess "$MNAME:standarderrors: bad index 1 $_[0]\n";
    }
    return (($K*($_[0]-1))+($_[1]-1));
  }
  sub giveuclear { 
    for (my $i=0; $i<($K**2); ++$i) { $u[$i]=0.0; }
    return (wantarray) ? @u : \@u;
  }

  sub u { return $u[ui($_[0], $_[1])]; }
  sub setu { return $u[ui($_[0], $_[1])]= $_[2]; }
  sub add2u { return $u[ui($_[0], $_[1])]+= $_[2]; }
  sub mult2u { return $u[ui($_[0], $_[1])]*= $_[2]; }

  (defined($K)) or confess "$MNAME:standarderrors: Internal Error: I forgot the number of variables.\n";
  if ($debug) {
    print "The Start Matrix is:\n";
    for (my $i=1; $i<=$K; ++$i) {
      print "[$i]:\t";
      for (my $j=1; $j<=$K; ++$j) {
	print $this->rbr($i, $j)."\t";
      }
      print "\n";
    }
    print "The Start d vector is:\n";
    for (my $i=1; $i<=$K; ++$i) {
      print "".$this->{d}[$i]."\t";
    }
    print "\n";
  }

  sub rbrindex {
    return ($_[0] == $_[1]) ? -9 :
      ($_[0]>$_[1]) ? -8 :
	((($_[0]-1.0)* (2.0*$K-$_[0])/2.0+1.0) + $_[1] - 1 - $_[0] ); }

  # now a real member routine;
  sub rbr {
    my $this= shift;
    return ($_[0] == $_[1]) ? 1 : ( ($_[0]>$_[1]) ? 0 : ($this->{rbar}[rbrindex($_[0],$_[1])]));
  }

  my $u= giveuclear();

  for (my $j=$K; $j>=1; --$j) {
    setu($j,$j, 1.0/($this->rbr($j,$j)));
    for (my $k=$j-1; $k>=1; --$k) {
      setu($k,$j,0);
      for (my $i=$k+1; $i<=$j; ++$i) { add2u($k,$j, $this->rbr($k,$i)*u($i,$j)); }
      mult2u($k,$j, (-1.0)/$this->rbr($k,$k));
    }
  }

  if ($debug) {
    print "The Inverse Matrix of R is:\n";
    for (my $i=1; $i<=$K; ++$i) {
      print "[$i]:\t";
      for (my $j=1; $j<=$K; ++$j) {
	print $u[ui($i,$j)]."\t";
      }
      print "\n";
    }
  }

  for (my $i=1;$i<=$K;++$i) {
    for (my $j=1;$j<=$K;++$j) {
      if (abs($this->{d}[$j])<TINY) {
	mult2u($i,$j, sqrt(1.0/TINY));
	if (abs($this->{d}[$j])==0.0) {
	  if ($this->{neverabort}) {
	    for (my $i=0; $i<($K**2); ++$i) { $u[$i]= "NaN"; }
	    return undef;
	  }
	  else { confess "$MNAME:standarderrors: I cannot compute the theta-covariance matrix for variable $j ".($this->{d}[$j])."\n"; }
	}
      }
      else { mult2u($i,$j, sqrt(1.0/$this->{d}[$j])); }
    }
  }

  if ($debug) {
    print "The Inverse Matrix of R multipled by D^(-1/2) is:\n";
    for (my $i=1; $i<=$K; ++$i) {
      print "[$i]:\t";
      for (my $j=1; $j<=$K; ++$j) {
	print $u[ui($i,$j)]."\t";
      }
      print "\n";
    }
  }

  $this->{sigmasq}= ($this->{n}<=$K) ? "Inf" : ($this->{sse}/($this->{n} - $K));
  my @xpxinv;
  for (my $i=1;$i<=$K; ++$i) {
    for (my $j=$i;$j<=$K;++$j) {
      my $indexij= ui($i,$j);
      $xpxinv[$indexij]= 0.0;
      for (my $k=1;$k<=$K;++$k) {
	$xpxinv[$indexij] += $u[ui($i,$k)]*$u[ui($j,$k)];
      }
      $xpxinv[ui($j,$i)]= $xpxinv[$indexij]; # this is symmetric
    }
  }

  if ($debug) {
    print "The full inverse matrix of X'X is:\n";
    for (my $i=1; $i<=$K; ++$i) {
      print "[$i]:\t";
      for (my $j=1; $j<=$K; ++$j) {
	print $xpxinv[ui($i,$j)]."\t";
      }
      print "\n";
    }
    print "The sigma^2 is ".$this->{sigmasq}."\n";
  }

  ## 99% of the usage here will be to print the diagonal elements of sqrt ( (X' X) sigma^2 )
  ## so, let's make this our first returned object;

  my @secoefs;
  for (my $i=1; $i<=$K; ++$i) {
    $secoefs[$i-1]= sqrt($xpxinv[ui($i,$i)] * $this->{sigmasq});
  }
  if ($debug) { for (my $i=0; $i<$K; ++$i) { print " $secoefs[$i] "; } print "\n"; }

  # the following are clever return methods;  if the user goes over the secoefs,
  # almost surely an error will result, because he will run into xpxinv.  For special
  # usage, however, xpxinv may still be useful.

  return ( @secoefs, \@xpxinv, $this->sigmasq );
}


################################
sub linearcombination_variance {
  my $this= shift;
  our $K= $this->{k};  # convenience

  my @linear= @_;

  ($#linear+1 == $K) or confess "$MNAME:linearcombination_variance: ".
    "Sorry, you must give a vector of length $K, not ".($#linear+1)."\n";

  my @allback= $this->standarderrors();  # compute everything we need;

  my $xpxinv= $allback[$this->{k}];
  my $sigmasq= $allback[$this->{k}+1];

  my $sum=0;
  for (my $i=1; $i<=$K; ++$i) {
    for (my $j=1; $j<=$K; ++$j) {
      $sum+= $linear[$i-1]*$linear[$j-1]*$xpxinv->[ui($i,$j)];
    }
  }
  $sum*=$sigmasq;
  return $sum;
}


################################################################
### sub dump() was used internally for debugging.
################################################################
sub dump {
  my $this= $_[0];
  print "****************************************************************\n";
  print "Regression '$this->{regname}'\n";
  print "****************************************************************\n";
  sub print1val {
    no strict;
    print "$_[1]($_[2])=\t". ((defined($_[0]->{ $_[2] }) ? $_[0]->{ $_[2] } : "intentionally undef"));

    my $ref=$_[0]->{ $_[2] };

    if (ref($ref) eq 'ARRAY') {
      my $arrayref= $ref;
      print " $#$arrayref+1 elements:\n";
      if ($#$arrayref>30) {
	print "\t";
	for(my $i=0; $i<$#$arrayref+1; ++$i) { print "$i='$arrayref->[$i]';"; }
	print "\n";
      }
      else {
	for(my $i=0; $i<$#$arrayref+1; ++$i) { print "\t$i=\t'$arrayref->[$i]'\n"; }
      }
    }
    elsif (ref($ref) eq 'HASH') {
      my $hashref= $ref;
      print " ".scalar(keys(%$hashref))." elements\n";
      while (my ($key, $val) = each(%$hashref)) {
	print "\t'$key'=>'$val';\n";
      }
    }
    else {
      print " [was scalar]\n"; }
  }

  while (my ($key, $val) = each(%$this)) {
    $this->print1val($key, $key);
  }
  print "****************************************************************\n";
}

################################################################
### The Test Program.  Invoke as "perl Regression.pm".
################################################################


if ($0 eq "Regression.pm") {

  # Create regression object
  my $reg = Statistics::Regression->new( "sample regression", [ "const", "someX", "someY" ] );

  # Add data points
  $reg->include( 2.0, [ 1.0, 3.0, -1.0 ] );
  $reg->include( 1.0, [ 1.0, 5.0, 2.0 ] );
  $reg->include( 20.0, [ 1.0, 31.0, 0.0 ] );

  my %inhash= ( const => 1.0, someX => 11.0, someY => 2.0, ignored => "ignored" );
  $reg->include( 15.0, \%inhash );
  # $reg->include( 15.0, [ 1.0, 11.0, 2.0 ] );

  # Print the result
  $reg->print();
}


1;
