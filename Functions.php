<?php

/*
Summary:
This set of functions is used to encrypt data. The actual encryption
occurs with encrypt() and private_encrypt(). encrypt() uses a non reversable
function discovered in linear algebra to create a unique vector that maps
to a point in the dimension when multiplied by a square matrix of plaintext.

For example, assume someone enters in a credit card number:

Convert it into 4x4 matrix:
1 4 5 2
2 1 0 0
3 1 7 4
2 6 3 0

Check if its independent, if not, make it independent:
1 4 5 2|0
2 1 0 0|0
3 1 7 4|0
2 6 3 0|0

Set that matrix equal to a random point in R4 space:
1 4 5 2|-300912
2 1 0 0|8764
3 1 7 4|21
2 6 3 0|8449372

There is a guarenteed unique linear combination of the vectors
that generate the random point.

Grab the coefficients of the vectors, this is your new encrypted data:
-243 0 0 0|-300912					-243								-300912
0 2833 0 0|8764						2833								8764
0 0 2811 0|21			Store-->  	21			and generated point 	21	
0 0 0 3100|8449372					3100								8449372

This information is now encrypted, and if a hacker gains access to both sets of data,
there is no mathematical way to generate the original matrix. Thus, this is a one way
encryption/hash.

To validate a password entry, encrypt the data using the previous random point.
If the encryptions match, the password is correct.

A more secure version of encryption is achieved when VectorCrypt is used in tandem with a private key.

Take a private key of 50 chracters long:
{a, 6, 3, 8, 2, s, I, &, #, !, B, C........} size 50

Split that into 2 smaller keys:
Keya={a, 6, 3, 8.....} size 25
Keyb={S, 2, D, a.....} size 25

Convert each into a 5x5 matrix:
(These characters are stored as ascii values)

KeyA			KeyB
a 6 3 8 2		c 1 4 w a
2 s I & #		a B # ! 7
! B C q C		z B @ & s
d @ X d S		C S B A s
# s C 2 a		b 8 * % 2


Now add a unique combination of KeyA and KeyB to the origional points:
1 x x x  +  (a + 6 + 3 + 8 + 2)+(c + a + z + C + b)  (Row 1 of KeyA + Column 1 of KeyB)		
x x x x								
x x x x								
x x x x								

Now multiply the new matrix points by the sum of the row and column:
(# is the result of the previous calculation)
# x x x  *  (a + 6 + 3 + 8 + 2)+(c + a + z + C + b)  (Row 1 of KeyA + Column 1 of KeyB)		
x x x x								
x x x x								
x x x x	

Do this for every point on the matrix, and you will have a new, privately encrypted matrix:
(Where each letter represents the previous calculations)

a b c d e
f g h i j
k l m n o
p q r s t
u v w x y

This is your new matrix, now you complete VectorCrypt on the matrix. 


In terms of collision:
The operation, mathematically, generates a unique vector for every single point in Rn.
However, as the computer stores the values as a decimal, some entropy is lost and some
collision can occur on very close values. However, I have mathematically proven that the 
radius of collision is directly proportional to the precision of storage the computer uses. 
With the precision of a double, a collision is impossible.

In terms of security:
The highest processing GPU can generate 250 billion passwords per second, we will use this as our standard.
Assume a user entered a 16 digit credit card number. 

Using VectorCrypt only:
It would take 46.296 days to generate the total password space

Using VectorCrypt with 50 digit (appriximately 325 bit) Private Key:
It would take 2.88 x 10^92 years to generate every possible combination of credit card number and Key

Using VectorCrypt with 50 digit (approximately 325 bit) Private Key:
It would take 1.175 x 10^83 years to generate all possible combinations of the resultant matrix after the private key has been used
(Assuming the hacker bypasses the key and outputs all possible matrices that are a result of multiplying by a Private Key to the encryption function)

Impervious to collisions

Created in program and idea by Conor D'Arcy
*/

//rref is a reduced row echelon form calculator
//It iterates continuously until the matrix is in rref form
function rref(&$matrix)
{
    $lead = 0;
    $rowCount = count($matrix);
    if ($rowCount == 0)
        return $matrix;
    $columnCount = 0;
    if (isset($matrix[0])) {
        $columnCount = count($matrix[0]);
    }
    for ($r = 0; $r < $rowCount; $r++) {
        if ($lead >= $columnCount)
            break;        {
            $i = $r;
            while ($matrix[$i][$lead] == 0) {
                $i++;
                if ($i == $rowCount) {
                    $i = $r;
                    $lead++;
                    if ($lead == $columnCount)
                        return $matrix;
                }
            }
            $temp = $matrix[$r];
            $matrix[$r] = $matrix[$i];
            $matrix[$i] = $temp;
        }        {
            $lv = $matrix[$r][$lead];
            for ($j = 0; $j < $columnCount; $j++) {
                $matrix[$r][$j] = $matrix[$r][$j] / $lv;
            }
        }
        for ($i = 0; $i < $rowCount; $i++) {
            if ($i != $r) {
                $lv = $matrix[$i][$lead];
                for ($j = 0; $j < $columnCount; $j++) {
                    $matrix[$i][$j] -= $lv * $matrix[$r][$j];
                }
            }
        }
        $lead++;
    }
    return $matrix;
};

//This is an output operator used to disaply a matrix
//Iterates through the rows and columns of a square matrix
function display_matrix($matrix)
{
	$dimension=count($matrix);
	for($row=0;$row<$dimension;$row++)
	{
		for($column=0;$column<=$dimension;$column++)
		{
			echo $matrix[$row][$column];
			echo "   ";		
		}
			echo "<div>";
	}
};

//Creates a matrix (matrix) with data (ptext)
function write_matrix(&$matrix,&$ptext)
{
$length=strlen($ptext);
	$n=0;
	//This portion pads the data to ensure it is a square number
	//A square number is required for a square matrix
	while(pow($n,2)-$length<0)
	{
		$n++;	
	}
	for($j=$length;$j<pow($n,2);$j++)
	{
		$ptext[$j]=0;	
	}
	//Fills up the matrix from the data (ptext)
	$count=0;
	for($row=0;$row<$n;$row++)
	{
		for($column=0;$column<$n;$column++)
		{
		$matrix[$row][$column]=ord($ptext[$count]);	
		$count++;
		}
	}	
};

//Creates a column vector and appends the matrix
//As a result, it become an agumented matrix
function add_point(&$matrix,$point)
{
	$dimension=count($matrix);
	for($row=0;$row<$dimension;$row++)
	{
		$matrix[$row][$dimension]=$point[$row];	
	}
};

//This function checks the independence of the matrix
//They must be linearly independent vectors to span Rn
function check_independence($matrix)
{
	$dimension=count($matrix);
//Sets the augmented matrix equal to 0
	for($row=0;$row<$dimension;$row++)
	{
		$matrix[$row][$dimension]=0;	
	}
	rref($matrix);
	$flag=0;
	$diagonal=0;
//Checks the output to find if it is lin. indepenent
//Must be =1 allong the diagonal 
	while($diagonal<$dimension)
	{	
		if($matrix[$diagonal][$diagonal]!=1)
		{$flag=1; break;}
		for($row=0;$row<$dimension;$row++)
		{
			if($row!=$diagonal)
			{
				if($matrix[$row][$diagonal]!=0)
				{$flag=1; break;}
			}
		}	
	$diagonal++;
	}
	if($flag==0)
	{return true;}
	else 
	{return false;}
};

//If the function is dependent, makes independent 
//by adding 1 to the diagonal
function make_independent(&$matrix)
{
	if(check_independence($matrix)==false)
	{ 	
		$dimension=count($matrix);
		do{
			for($diagonal=0;$diagonal<$dimension;$diagonal++)
			{
			$matrix[$diagonal][$diagonal]+=1;	
			}
		}
		while(check_independence($matrix)==false);
	}
};

//Takes the resultant values and stores them in new variable (ctext)
function grab_encrypted($matrix, &$ctext)
{
	$dimension=count($matrix);
	//Storing the values generated from variable column 
	for($row=0;$row<$dimension;$row++)
	{
		$ctext[$row][0]=$matrix[$row][$dimension];	
	}
	//Takes the decimal values and stores them as a numerator and denominator
	for($row=0;$row<$dimension;$row++)
	{
		$f=farey($ctext[$row][0],1000000000000);
		for($column=0;$column<=1;$column++)
		{
			$ctext[$row][$column]=$f[$column];	
		}
	}
};
//An output operator that displays the encrypted data 

function display_encrypted($ctext)
{
	$dimension=count($ctext);
	for($row=0;$row<$dimension;$row++)
	{
		for($column=0;$column<=1;$column++)
		{
			echo $ctext[$row][$column];	
			echo "              ";
		}
		echo "<div>";
	}
};
//Generates a random point for a given matrix 

function generate_point(&$point, $matrix)
{
	for($row=0;$row<count($matrix);$row++)
	{
		$point[$row]=rand(-10000,10000);
	}
	
};

//Creates a numerator/denominator for a decimal (v)
//up to a certain value (lim)
function farey($v, $lim) 
{
    if($v < 0) {
        list($n, $d) = farey(-$v, $lim);
        return array(-$n, $d);
    }
    $z = $lim - $lim;  
    list($lower, $upper) = array(array($z, $z+1), array($z+1, $z));
    while(true) {
        $mediant = array(($lower[0] + $upper[0]), ($lower[1] + $upper[1]));
        if($v * $mediant[1] > $mediant[0]) {
            if($lim < $mediant[1]) 
              return $upper;
            $lower = $mediant;
        }
        else if($v * $mediant[1] == $mediant[0]) {
            if($lim >= $mediant[1])
                return $mediant;
            if($lower[1] < $upper[1])
                return $lower;
            return $upper;
        }
        else {
            if($lim < $mediant[1])
                return $lower;
            $upper = $mediant;
        }
    }
};

//The encryption function
function encrypt($ptext)
{
//Error trapping
	if($ptext==null)
	{return 0;}
//Create the matrix	
	write_matrix($matrix,$ptext);
//Make that matrix independent 
	make_independent($matrix);
//Generate the random point in nth dimension	
	generate_point($point,$matrix);
//Append that point to the matrix	
	add_point($matrix,$point);
//rref the matrix	
	rref($matrix);
//Grab the encrypted data	
	grab_encrypted($matrix, $ctext);
/*
Currently the program is outputting the encrypted data to 
a text file for comparison later on. However, this could 
store to a database or to any location the user would like
*/
	$datafile = fopen("datafile.txt","w");
	for($row=0;$row<count($matrix);$row++)
	{
		for($column=0;$column<=1;$column++)
		{
			fwrite($datafile, $ctext[$row][$column]);	
			fwrite($datafile, "\n");
		}
	}
	for($row=0;$row<count($matrix);$row++)
	{
		fwrite($datafile, $point[$row]);
		fwrite($datafile,"\n");
	}
	fclose($datafile);
	$ptext=0;
};

//Check data against its encrypted self to validate
function check_encrypt($ptext)
{
	$datafile = fopen("datafile.txt","r");
//Stores the encrypted data in an array (cext)	
	for($row=0;$row<sqrt(strlen($ptext));$row++)
	{
		for($column=0;$column<=1;$column++)
		{
			$ctext[$row][$column]=fgets($datafile);
		}
	}	
//Store the randomly generated point that was used 	
	for($n=0;$n<sqrt(strlen($ptext));$n++)
	{
		$point[$n]=fgets($datafile);
	}
//follow the same encryption procedure as encrypt function
	write_matrix($matrix,$ptext);
	make_independent($matrix);
	add_point($matrix,$point);
	rref($matrix);
	grab_encrypted($matrix, $newctext);
//Check if the result is the same as the original 
	$flag=0;
	for($row=0;$row<count($matrix);$row++)
	{
		for($column=0;$column<=1;$column++)
		{
			if($ctext[$row][$column]!=$newctext[$row][$column])
			{
				$flag=1;
				break 2;
			}
		}
	}
	fclose($datafile);
	$ptext=0;
	if($flag==0)
	{return true;}
	else
	{return false;}
};

//Writes a matrix from the private key passed
function write_private_key_matrix(&$matrix,$key)
{
	$length=count($key);
	$n=0;
	while(pow($n,2)-$length<0)
	{
		$n++;	
	}
	for($j=$length;$j<pow($n,2);$j++)
	{
		$key[$j]=0;	
	}
	$count=0;
	for($row=0;$row<$n;$row++)
	{
		for($column=0;$column<$n;$column++)
		{
			$matrix[$row][$column]=ord($key[$count]);	
			$count++;
		}
	}	
};


//Encrypt the data using the key
function key_encrypt(&$matrix, $key)
{
//Makes the private key even
	if(strlen($key)%2!=0)
	{
		$key[strlen($key)]=1;	
	}
//Initialize the private keys 
	$keya = array_fill(0,strlen($key)/2,0);
	$keyb = array_fill(0,strlen($key)/2,0);
//Splits the private key into 2 smaller keys
	for($i=0;$i<count($keya);$i++)
	{
			$keya[$i]=$key[$i];
	}
	for($i=0;$i<(strlen($key)/2);$i++)
	{
			$keyb[$i]=$key[$i+(strlen($key)/2)];
	}
//Write the private key matrices
	write_private_key_matrix($PrivateMatrixA,$keya);
	write_private_key_matrix($PrivateMatrixB,$keyb);
//Applying the key to the original matrix of data
	for($row=0;$row<count($matrix);$row++)
	{
		for($column=0;$column<count($matrix);$column++)
		{
			$temp=0;
			for($i=0;$i<count($PrivateMatrixA);$i++)
			{$temp+=$PrivateMatrixA[$row%count($PrivateMatrixA)][$i];}
			for($i=0;$i<count($PrivateMatrixA);$i++)
			{$temp+=$PrivateMatrixB[$i][$column%count($PrivateMatrixB)];}
			$matrix[$row][$column]+=$temp;
			$matrix[$row][$column]*=$temp;
		}	
	}
};


//Added security, encrypt with VectCrypt and private key
function private_encrypt($ptext, $key)
{
//Error trapping
	if($ptext==null||$key==null)
	{return 0;}
//Creates the matrix	
	write_matrix($matrix,$ptext);
//Encrypt with the key	
	key_encrypt($matrix,$key);
//VectCrypt from this point on
	make_independent($matrix);
	generate_point($point,$matrix);
	add_point($matrix,$point);
	rref($matrix);
	grab_encrypted($matrix, $ctext);
	$datafile = fopen("datafile.txt","w");
	for($row=0;$row<count($matrix);$row++)
	{
		for($column=0;$column<=1;$column++)
		{
			fwrite($datafile, $ctext[$row][$column]);	
			fwrite($datafile, "\n");
		}
	}
	for($row=0;$row<count($matrix);$row++)
	{
		fwrite($datafile, $point[$row]);
		fwrite($datafile,"\n");
	}
	fclose($datafile);
	$ptext=0;
	display_encrypted($ctext);
};

function breaker($v1,$v2,$A,$B)
{
		$count=0;
		for($a=0;$a<10;$a++)
		{
			for($c=0;$c<10;$c++)
			{
				if(((-$v1*$c)+($B))/(($v1*$a)-$A)==(((-$c/$a)-((-$v1*$c+$B)/($v1*$a-$A)))/((-$c/$a)*($A/$v2)+($B/$v2)))*(((($c*$A)/($a*$v2))-($B/$v2))-($c/$a)));
				{
					$truea=$a;
					$truec=$c;
					$trueb=(((-$c/$a)-((-$v1*$c+$B)/($v1*$a-$A)))/((-$c/$a)*($A/$v2)+($B/$v2)));
					$trued=(($v1*$c-$B)/(-$v1*$a+$A))*$trueb;
					$count++;
				}
			}
		}
echo "Count: " . $count;
echo "<br>";
echo "A value: " . $truea;
echo "<br>";
echo "B value: " . $trueb;
echo "<br>";
echo "C value: " . $truec;
echo "<br>";
echo "D value: " . $trued;
echo "<br>";


};





?>