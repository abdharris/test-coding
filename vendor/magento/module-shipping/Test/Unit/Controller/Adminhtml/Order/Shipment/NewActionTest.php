<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Shipping\Test\Unit\Controller\Adminhtml\Order\Shipment;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Helper\Data;
use Magento\Backend\Model\Session;
use Magento\Framework\App\ActionFlag;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\ViewInterface;
use Magento\Framework\Message\Manager;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Backend\Model\View\Result\Page;
use Magento\Sales\Model\Order\Shipment;
use Magento\Shipping\Controller\Adminhtml\Order\Shipment\NewAction;
use Magento\Shipping\Controller\Adminhtml\Order\ShipmentLoader;
use Magento\Shipping\Model\ShipmentProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\Framework\View\Result\PageFactory;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.TooManyFields)
 */
class NewActionTest extends TestCase
{
    /**
     * @var ShipmentLoader|MockObject
     */
    protected $shipmentLoader;

    /**
     * @var NewAction
     */
    protected $newAction;

    /**
     * @var Action\Context|MockObject
     */
    protected $context;

    /**
     * @var Http|MockObject
     */
    protected $request;

    /**
     * @var ResponseInterface|MockObject
     */
    protected $response;

    /**
     * @var Manager|MockObject
     */
    protected $messageManager;

    /**
     * @var \Magento\Framework\ObjectManager\ObjectManager|MockObject
     */
    protected $objectManager;

    /**
     * @var Session|MockObject
     */
    protected $session;

    /**
     * @var ActionFlag|MockObject
     */
    protected $actionFlag;

    /**
     * @var Data|MockObject
     */
    protected $helper;

    /**
     * @var  ViewInterface|MockObject
     */
    protected $view;

    /**
     * @var Page|MockObject
     */
    protected $resultPageMock;

    /**
     * @var Config|MockObject
     */
    protected $pageConfigMock;

    /**
     * @var Title|MockObject
     */
    protected $pageTitleMock;

    /**
     * @var ShipmentProviderInterface|MockObject
     */
    private $shipmentProviderMock;

    /**
     * @var PageFactory|MockObject
     */
    protected $resultPageFactoryMock;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function setUp(): void
    {
        $objectManagerHelper = new ObjectManagerHelper($this);
        $this->shipmentLoader = $this->getMockBuilder(
            ShipmentLoader::class
        )->disableOriginalConstructor()
            ->setMethods(['setShipmentId', 'setOrderId', 'setShipment', 'setTracking', 'load'])
            ->getMock();
        $this->context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMock();
        $this->objectManager = $this->getMockForAbstractClass(ObjectManagerInterface::class);
        $this->context = $this->createPartialMock(Context::class, [
            'getRequest', 'getResponse', 'getMessageManager', 'getRedirect', 'getObjectManager',
            'getSession', 'getActionFlag', 'getHelper', 'getView'
        ]);
        $this->response = $this->getMockBuilder(ResponseInterface::class)
            ->addMethods(['setRedirect'])
            ->onlyMethods(['sendResponse'])
            ->getMockForAbstractClass();
        $this->request = $this->getMockBuilder(Http::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->messageManager = $this->createPartialMock(
            Manager::class,
            ['addSuccess', 'addError']
        );
        $this->session = $this->getMockBuilder(Session::class)
            ->addMethods(['setIsUrlNotice', 'getCommentText'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->shipmentProviderMock = $this->getMockBuilder(ShipmentProviderInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getShipmentData'])
            ->getMockForAbstractClass();
        $this->actionFlag = $this->createPartialMock(ActionFlag::class, ['get']);
        $this->helper = $this->createPartialMock(Data::class, ['getUrl']);
        $this->view = $this->getMockForAbstractClass(ViewInterface::class);
        $this->resultPageMock = $this->getMockBuilder(Page::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['setActiveMenu','getConfig'])
            ->getMock();
        $this->resultPageFactoryMock = $this->getMockBuilder(PageFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $this->pageConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->pageTitleMock = $this->getMockBuilder(Title::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->context->expects($this->once())
            ->method('getMessageManager')
            ->willReturn($this->messageManager);
        $this->context->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);
        $this->context->expects($this->once())
            ->method('getResponse')
            ->willReturn($this->response);
        $this->context->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($this->objectManager);
        $this->context->expects($this->once())
            ->method('getSession')
            ->willReturn($this->session);
        $this->context->expects($this->once())
            ->method('getActionFlag')
            ->willReturn($this->actionFlag);
        $this->context->expects($this->once())
            ->method('getHelper')
            ->willReturn($this->helper);
        $this->context->expects($this->once())->method('getView')->willReturn($this->view);
        $this->newAction = $objectManagerHelper->getObject(
            NewAction::class,
            [
                'context' => $this->context,
                'resultPageFactory' => $this->resultPageFactoryMock,
                'shipmentLoader' => $this->shipmentLoader,
                'request' => $this->request,
                'response' => $this->response,
                'shipmentProvider' => $this->shipmentProviderMock
            ]
        );
    }

    public function testExecute()
    {
        $shipmentId = 1000012;
        $orderId = 10003;
        $tracking = [];
        $shipmentData = ['items' => [], 'send_email' => ''];
        $shipment = $this->createPartialMock(
            Shipment::class,
            ['load', 'save', 'register', 'getOrder', 'getOrderId', '__wakeup']
        );
        $this->request->expects($this->any())
            ->method('getParam')
            ->willReturnMap(
                [
                    ['order_id', null, $orderId],
                    ['shipment_id', null, $shipmentId],
                    ['tracking', null, $tracking],
                ]
            );
        $this->shipmentLoader->expects($this->any())
            ->method('setShipmentId')
            ->with($shipmentId);
        $this->shipmentLoader->expects($this->any())
            ->method('setOrderId')
            ->with($orderId);
        $this->shipmentLoader->expects($this->any())
            ->method('setShipment')
            ->with($shipmentData);
        $this->shipmentLoader->expects($this->any())
            ->method('setTracking')
            ->with($tracking);
        $this->shipmentLoader->expects($this->once())
            ->method('load')
            ->willReturn($shipment);
        $this->session->expects($this->once())
            ->method('getCommentText')
            ->with(true)
            ->willReturn('');
        $this->objectManager->expects($this->atLeastOnce())
            ->method('get')
            ->with(Session::class)
            ->willReturn($this->session);
        $this->resultPageMock->expects($this->any())
            ->method('getConfig')
            ->willReturn($this->pageConfigMock);
        $this->pageConfigMock->expects($this->any())
            ->method('getTitle')
            ->willReturn($this->pageTitleMock);

        $this->resultPageFactoryMock->expects($this->once())
            ->method('create')
            ->willReturn($this->resultPageMock);
        $this->resultPageMock->expects($this->once())
            ->method('setActiveMenu')
            ->with('Magento_Sales::sales_order')
            ->willReturnSelf();
        $this->resultPageMock->expects($this->atLeastOnce())
            ->method('getConfig')
            ->willReturn($this->pageConfigMock);

        $this->shipmentProviderMock->expects($this->once())
            ->method('getShipmentData')
            ->willReturn($shipmentData);

        $this->assertInstanceOf(
            Page::class,
            $this->newAction->execute()
        );
    }
}
