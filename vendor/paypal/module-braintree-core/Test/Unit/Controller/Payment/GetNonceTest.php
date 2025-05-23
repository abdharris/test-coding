<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace PayPal\Braintree\Test\Unit\Controller\Payment;

use Magento\Framework\Exception\NotFoundException;
use PayPal\Braintree\Controller\Payment\GetNonce;
use PayPal\Braintree\Gateway\Command\GetPaymentNonceCommand;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Framework\Webapi\Exception;
use Magento\Payment\Gateway\Command\ResultInterface as CommandResultInterface;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class GetNonceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var GetNonce
     */
    private GetNonce $action;

    /**
     * @var GetPaymentNonceCommand|MockObject
     */
    private GetPaymentNonceCommand|MockObject $command;

    /**
     * @var Session|MockObject
     */
    private Session|MockObject $session;

    /**
     * @var LoggerInterface|MockObject
     */
    private LoggerInterface|MockObject $logger;

    /**
     * @var ResultFactory|MockObject
     */
    private ResultFactory|MockObject $resultFactory;

    /**
     * @var ResultInterface|MockObject
     */
    private ResultInterface|MockObject $result;

    /**
     * @var Http|MockObject
     */
    private Http|MockObject $request;

    /**
     * @var CommandResultInterface|MockObject
     */
    private CommandResultInterface|MockObject $commandResult;

    protected function setUp(): void
    {
        $this->initResultFactoryMock();

        $this->request = $this->getMockBuilder(RequestInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParam'])
            ->getMockForAbstractClass();

        $this->command = $this->getMockBuilder(GetPaymentNonceCommand::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute', '__wakeup'])
            ->getMock();

        $this->commandResult = $this->getMockBuilder(CommandResultInterface::class)
            ->setMethods(['get'])
            ->getMockForAbstractClass();

        $this->session = $this->getMockBuilder(Session::class)
            ->disableOriginalConstructor()
            ->setMethods(['getCustomerId'])
            ->getMock();

        $this->logger = $this->createMock(LoggerInterface::class);

        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects(static::any())
            ->method('getRequest')
            ->willReturn($this->request);
        $context->expects(static::any())
            ->method('getResultFactory')
            ->willReturn($this->resultFactory);

        $managerHelper = new ObjectManager($this);
        $this->action = $managerHelper->getObject(GetNonce::class, [
            'context' => $context,
            'logger' => $this->logger,
            'session' => $this->session,
            'command' => $this->command
        ]);
    }

    /**
     * @covers \PayPal\Braintree\Controller\Payment\GetNonce::execute
     * @throws NotFoundException
     */
    public function testExecuteWithException()
    {
        $this->request->expects(static::once())
            ->method('getParam')
            ->with('public_hash')
            ->willReturn(null);

        $this->session->expects(static::once())
            ->method('getCustomerId')
            ->willReturn(null);

        $exception = new \Exception('The "publicHash" field does not exists');
        $this->command->expects(static::once())
            ->method('execute')
            ->willThrowException($exception);

        $this->logger->expects(static::once())
            ->method('critical')
            ->with($exception);

        $this->result->expects(static::once())
            ->method('setHttpResponseCode')
            ->with(Exception::HTTP_BAD_REQUEST);
        $this->result->expects(static::once())
            ->method('setData')
            ->with(['message' => 'Sorry, but something went wrong']);

        $this->action->execute();
    }

    /**
     * @covers \PayPal\Braintree\Controller\Payment\GetNonce::execute
     */
    public function testExecute()
    {
        $customerId = 1;
        $publicHash = '65b7bae0dcb690d93';
        $nonce = 'f1hc45';
        $details = ['bin' => '41111'];

        $this->request->expects(static::once())
            ->method('getParam')
            ->with('public_hash')
            ->willReturn($publicHash);

        $this->session->expects(static::once())
            ->method('getCustomerId')
            ->willReturn($customerId);

        $this->commandResult->expects(static::once())
            ->method('get')
            ->willReturn([
                'paymentMethodNonce' => $nonce,
                'details' => $details
            ]);
        $this->command->expects(static::once())
            ->method('execute')
            ->willReturn($this->commandResult);

        $this->result->expects(static::once())
            ->method('setData')
            ->with([
                'paymentMethodNonce' => $nonce,
                'details' => $details
            ]);

        $this->logger->expects(static::never())
            ->method('critical');

        $this->result->expects(static::never())
            ->method('setHttpResponseCode');

        $this->action->execute();
    }

    /**
     * Create mock for result factory object
     */
    private function initResultFactoryMock(): void
    {
        $this->result = $this->getMockBuilder(ResultInterface::class)
            ->setMethods(['setHttpResponseCode', 'renderResult', 'setHeader', 'setData'])
            ->getMockForAbstractClass();

        $this->resultFactory = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->resultFactory->expects(static::once())
            ->method('create')
            ->willReturn($this->result);
    }
}
