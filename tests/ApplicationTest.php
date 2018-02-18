<?php
namespace Mill\Tests;

use Mill\Application;

class ApplicationTest extends TestCase
{
    public function testApplication()
    {
        $config = $this->getConfig();
        $application = new Application($config);
        $application->preload();

print_r($application);exit;
    }
}
