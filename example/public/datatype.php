<?php

class Foo {
	
}

class Bar extends Foo {

	public function doSomething ()
	{
		
	}
	
}

function doSomething (Foo $obj)
{
	\Neuron\Exceptions\ExpectedType::check ($obj, Foo::class);
	
	$obj->doSomething ();
}

$mi = new Bar ();

