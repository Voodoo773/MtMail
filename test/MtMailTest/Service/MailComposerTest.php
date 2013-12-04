<?php

namespace MtMailTest\Service;

use MtMail\Event\ComposerEvent;
use MtMail\Factory\MailComposerFactory;
use MtMail\Service\MailComposer;
use MtMailTest\Test\Template;
use Zend\EventManager\EventManager;
use Zend\View\Model\ViewModel;

class MailComposerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MailComposer
     */
    protected $service;

    public function setUp()
    {
        $renderer = $this->getMock('MtMail\Renderer\RendererInterface');
        $this->service = new MailComposer($renderer);
    }

    public function testRendererIsMutable()
    {
        $renderer = $this->getMock('MtMail\Renderer\RendererInterface');
        $this->assertEquals($renderer, $this->service->setRenderer($renderer)->getRenderer());
    }

    public function testComposeRendersViewModelAndAssignsResultToMailBody()
    {
        $template = new Template();

        $renderer = $this->getMock('MtMail\Renderer\RendererInterface', array('render'));
        $renderer->expects($this->once())->method('render')->with($this->isInstanceOf('Zend\View\Model\ModelInterface'))
            ->will($this->returnValue('MAIL_BODY'));

        $service = new MailComposer($renderer);
        $message = $service->compose($template);
        $this->assertEquals('MAIL_BODY', $message->getBody()->getPartContent(0));
    }

    public function testComposeRendersViewModelAndAssignsSubjectIfProvidedByViewModel()
    {
        $template = new Template();

        $renderer = $this->getMock('MtMail\Renderer\RendererInterface', array('render'));
        $renderer->expects($this->once())->method('render')->with($this->isInstanceOf('Zend\View\Model\ModelInterface'))
            ->will($this->returnValue('MAIL_BODY'));

        $service = new MailComposer($renderer);
        $message = $service->compose($template, null, array('subject' => 'MAIL_SUBJECT'));
        $this->assertEquals('MAIL_BODY', $message->getBody()->getPartContent(0));
        $this->assertEquals('MAIL_SUBJECT', $message->getSubject());
    }

    public function testServiceIsEventManagerAware()
    {
        $em = new EventManager();
        $this->service->setEventManager($em);
        $this->assertEquals($em, $this->service->getEventManager());
    }

    public function testComposeTriggersEvents()
    {
        $renderer = $this->getMock('MtMail\Renderer\RendererInterface', array('render'));
        $renderer->expects($this->once())->method('render')->with($this->isInstanceOf('Zend\View\Model\ModelInterface'))
            ->will($this->returnValue('MAIL_BODY'));

        $em = $this->getMock('Zend\EventManager\EventManager', array('trigger'));
        $em->expects($this->at(0))->method('trigger')->with(ComposerEvent::EVENT_COMPOSE_PRE, $this->isInstanceOf('MtMail\Event\ComposerEvent'));
        $em->expects($this->at(1))->method('trigger')->with(ComposerEvent::EVENT_HEADERS_PRE, $this->isInstanceOf('MtMail\Event\ComposerEvent'));
        $em->expects($this->at(2))->method('trigger')->with(ComposerEvent::EVENT_HEADERS_POST, $this->isInstanceOf('MtMail\Event\ComposerEvent'));
        $em->expects($this->at(3))->method('trigger')->with(ComposerEvent::EVENT_HTML_BODY_PRE, $this->isInstanceOf('MtMail\Event\ComposerEvent'));
        $em->expects($this->at(4))->method('trigger')->with(ComposerEvent::EVENT_HTML_BODY_POST, $this->isInstanceOf('MtMail\Event\ComposerEvent'));
        $em->expects($this->at(5))->method('trigger')->with(ComposerEvent::EVENT_COMPOSE_POST, $this->isInstanceOf('MtMail\Event\ComposerEvent'));

        $service = new MailComposer($renderer);
        $service->setEventManager($em);
        $template = new Template();
        $service->compose($template);
    }

    public function testHtmlBodyPreEventAllowsReplacingViewModel()
    {
        $replacement = new ViewModel();
        $replacement->setTemplate('some_template.phtml');
        $renderer = $this->getMock('MtMail\Renderer\RendererInterface', array('render'));
        $renderer->expects($this->once())->method('render')->with($this->equalTo($replacement))
            ->will($this->returnValue('MAIL_BODY'));

        $service = new MailComposer($renderer);
        $template = new Template();

        $service->getEventManager()->attach(ComposerEvent::EVENT_HTML_BODY_PRE, function ($event) use ($replacement) {
                $event->setViewModel($replacement);
            });

        $service->compose($template);
    }

}
