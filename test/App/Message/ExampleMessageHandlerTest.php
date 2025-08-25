<?php

declare(strict_types=1);

namespace QueueTest\App\Message;

use Core\User\Repository\UserRepository;
use Dot\Log\Logger;
use Dot\Mail\Email;
use Dot\Mail\Exception\MailException;
use Dot\Mail\Service\MailService;
use Exception;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Queue\App\Message\Message;
use Queue\App\Message\MessageHandler;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

use function json_encode;

class ExampleMessageHandlerTest extends TestCase
{
    protected MailService|MockObject $mailService;
    protected TemplateRendererInterface|MockObject $renderer;
    protected UserRepository|MockObject $userRepository;
    protected Logger $logger;
    protected array $config;
    private MessageHandler $handler;

    /**
     * @throws Exception|\PHPUnit\Framework\MockObject\Exception
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->mailService    = $this->createMock(MailService::class);
        $this->renderer       = $this->createMock(TemplateRendererInterface::class);
        $this->userRepository = $this->createMock(UserRepository::class);
        $this->logger         = new Logger([
            'writers' => [
                'FileWriter' => [
                    'name'  => 'null',
                    'level' => Logger::ALERT,
                ],
            ],
        ]);
        $this->config         = [
            'notification' => [
                'server' => [
                    'protocol' => 'tcp',
                    'host'     => 'localhost',
                    'port'     => '8556',
                    'eof'      => "\n",
                ],
            ],
            'application'  => [
                'name' => 'dotkernel',
            ],
        ];

        $this->handler = new MessageHandler(
            $this->mailService,
            $this->renderer,
            $this->userRepository,
            $this->logger,
            $this->config
        );
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testInvokeHandlesExceptionThrownByPerform(): void
    {
        $uuid = '1234';

        $message = $this->createMock(Message::class);
        $message->method('getPayload')->willReturn([
            'foo' => json_encode(['userUuid' => $uuid]),
        ]);

        $handlerMock = $this->getMockBuilder(MessageHandler::class)
            ->setConstructorArgs([
                $this->mailService,
                $this->renderer,
                $this->userRepository,
                $this->logger,
                $this->config,
            ])
            ->onlyMethods(['perform'])
            ->getMock();

        $handlerMock
            ->expects($this->once())
            ->method('perform')
            ->willThrowException(new \RuntimeException('Test exception'));

        $handlerMock->__invoke($message);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testInvokeWithValidPayload(): void
    {
        $uuid  = '1245';
        $email = 'test@dotkernel.com';
        $name  = 'dotkernel';

        $message = $this->createMock(Message::class);
        $message->method('getPayload')->willReturn([
            'foo' => json_encode(['userUuid' => $uuid]),
        ]);

        $user = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getEmail', 'getName'])
            ->getMock();
        $user->method('getEmail')->willReturn($email);
        $user->method('getName')->willReturn($name);

        $this->userRepository
            ->expects($this->once())
            ->method('find')
            ->with($uuid)
            ->willReturn($user);

        $mailMessage = $this->createMock(Email::class);
        $mailMessage->expects($this->once())
            ->method('addTo')
            ->with($email, $name);

        $this
            ->mailService
            ->method('getMessage')
            ->willReturn($mailMessage);

        $this
            ->mailService
            ->expects($this->once())
            ->method('setSubject')
            ->with('Welcome to dotkernel');

        $this->renderer->method('render')->willReturn('Rendered email body');

        $this->mailService->expects($this->once())
            ->method('setBody')
            ->with('Rendered email body');

        $this->handler->__invoke($message);
    }

    /**
     * @throws MailException
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testSendWelcomeMailHandlesMailException(): void
    {
        $uuid = '1234';

        $reflection   = new \ReflectionClass($this->handler);
        $argsProperty = $reflection->getProperty('args');
        $argsProperty->setValue($this->handler, ['userUuid' => $uuid]);

        $user = $this->getMockBuilder(\stdClass::class)
            ->addMethods(['getEmail', 'getName'])
            ->getMock();
        $user->method('getEmail')->willReturn('test@dotkernel.com');
        $user->method('getName')->willReturn('dotkernel');

        $this->userRepository->method('find')->willReturn($user);
        $this->mailService->method('getMessage')->willReturn($this->createMock(Email::class));
        $this->mailService->method('setSubject');
        $this->renderer->method('render')->willReturn('Rendered content');
        $this->mailService->method('setBody');

        $this->mailService->method('send')
            ->willThrowException($this->createMock(TransportExceptionInterface::class));

        $result = $this->handler->sendWelcomeMail();
        $this->assertFalse($result);
    }

    /**
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function testInvokeWithInvalidJsonSkipsPerform(): void
    {
        $message = $this->createMock(Message::class);
        $message->method('getPayload')->willReturn([
            'foo' => '{"userUuid":',
        ]);

        $this->userRepository->expects($this->never())->method('find');
        $this->mailService->expects($this->never())->method('send');

        $this->handler->__invoke($message);
    }
}
