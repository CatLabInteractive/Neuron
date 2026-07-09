<?php

use Neuron\Net\Response;
use Neuron\Net\Outputs\Output;
use PHPUnit\Framework\TestCase;

class CapturingTestOutput implements Output
{
    public $captured = array();

    public function output(Response $response)
    {
        $this->captured[] = $response;
    }
}

class ResponseOverrideOutputTest extends TestCase
{
    protected function tearDown(): void
    {
        Response::overrideOutput(null);
    }

    public function testOverrideCapturesInsteadOfEchoing()
    {
        $capture = new CapturingTestOutput();
        Response::overrideOutput($capture);

        $response = Response::json(array('hello' => 'world'));
        ob_start();
        $response->output();
        $echoed = ob_get_clean();

        $this->assertSame('', $echoed, 'override must swallow stdout output');
        $this->assertCount(1, $capture->captured);
        $this->assertSame($response, $capture->captured[0]);
    }

    public function testClearingOverrideRestoresNormalOutput()
    {
        $capture = new CapturingTestOutput();
        Response::overrideOutput($capture);
        Response::overrideOutput(null);

        $response = Response::json(array('hello' => 'world'));
        ob_start();
        $response->output();
        $echoed = ob_get_clean();

        $this->assertSame('{"hello":"world"}', $echoed);
    }
}
