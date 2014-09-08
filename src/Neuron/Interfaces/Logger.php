<?php

namespace Neuron\Interfaces;


interface Logger
{
	public function log ($string, $replace = false, $color = null);
};