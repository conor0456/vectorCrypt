<?php

require "Functions.php";

function private_encrypt($ptext, $key)
{
	if($ptext==null||$key==null)
	{return 0;}
	
	write_matrix($matrix,$ptext);

	//make even
	if(strlen($key)%2!=0)
	{
		$key[strlen($key)]=1;	
	}
	//initialize private keys
	$keya = array_fill(0,strlen($key)/2,0);
	$keyb = array_fill(0,strlen($key)/2,0);

	for($i=0;$i<count($keya);$i++)
	{
			$keya[$i]=$key[$i];
	}
	for($i=0;$i<(strlen($key)/2);$i++)
	{
			$keyb[$i]=$key[$i+(strlen($key)/2)];
	}
	//write the private key matrices
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
}

?>