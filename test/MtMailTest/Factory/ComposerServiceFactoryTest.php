<?php
/**
 * MtMail - e-mail module for Zend Framework 2
 *
 * @link      http://github.com/mtymek/MtMail
 * @copyright Copyright (c) 2013-2014 Mateusz Tymek
 * @license   BSD 2-Clause
 */

namespace MtMailTest\Factory;

use Interop\Container\ContainerInterface;
use MtMail\Factory\ComposerServiceFactory;

class ComposerServiceFactoryTest extends \PHPUnit_Framework_TestCase
{

    public function testCreateService()
    {
        $locator = $this->getMock(ContainerInterface::class, ['get', 'has']);
        $locator->expects($this->at(0))->method('get')
            ->with('Configuration')->will(
                $this->returnValue(
                    [
                        'mt_mail' => [
                            'renderer' => 'Some\Mail\Renderer',
                        ],
                    ]
                )
            );

        $renderer = $this->getMock('MtMail\Renderer\RendererInterface', ['render']);
        $locator->expects($this->at(1))->method('get')
            ->with('Some\Mail\Renderer')->will(
                $this->returnValue($renderer)
            );

        $factory = new ComposerServiceFactory();
        $service = $factory($locator);
        $this->assertInstanceOf('MtMail\Service\Composer', $service);
        $this->assertEquals($renderer, $service->getRenderer());
    }

    public function testCreateServiceCanInjectPlugins()
    {
        $locator = $this->getMock(ContainerInterface::class, ['get', 'has']);
        $locator->expects($this->at(0))->method('get')
            ->with('Configuration')->will(
                $this->returnValue(
                    [
                        'mt_mail' => [
                            'renderer' => 'Some\Mail\Renderer',
                            'composer_plugins' => [
                                'SomeMailPlugin',
                                'SomeMailPlugin',
                            ],
                        ],
                    ]
                )
            );
        $renderer = $this->getMock('MtMail\Renderer\RendererInterface', ['render']);
        $locator->expects($this->at(1))->method('get')
            ->with('Some\Mail\Renderer')->will(
                $this->returnValue($renderer)
            );

        $plugin = $this->getMock('MtMail\ComposerPlugin\PluginInterface');
        $pluginManager = $this->getMock('MtMail\Service\ComposerPluginManager', ['get'], [], '', false);
        $pluginManager->expects($this->once())->method('get')->with('SomeMailPlugin')
            ->will($this->returnValue($plugin));
        $locator->expects($this->at(2))->method('get')
            ->with('MtMail\Service\ComposerPluginManager')->will(
                $this->returnValue($pluginManager)
            );


        $factory = new ComposerServiceFactory();
        $service = $factory($locator);
        $this->assertInstanceOf('MtMail\Service\Composer', $service);
        $this->assertEquals($renderer, $service->getRenderer());
    }
}
