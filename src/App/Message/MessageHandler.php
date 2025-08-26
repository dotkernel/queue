<?php

declare(strict_types=1);

namespace Queue\App\Message;

use Core\User\Repository\UserRepository;
use Dot\DependencyInjection\Attribute\Inject;
use Dot\Log\Logger;
use Dot\Mail\Exception\MailException;
use Dot\Mail\Service\MailService;
use Exception;
use Mezzio\Template\TemplateRendererInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

use function json_decode;

class MessageHandler
{
    protected array $args = [];

    #[Inject(
        MailService::class,
        TemplateRendererInterface::class,
        UserRepository::class,
        'dot-log.queue-log',
        'config',
    )]
    public function __construct(
        protected MailService $mailService,
        protected TemplateRendererInterface $templateRenderer,
        protected UserRepository $userRepository,
        protected Logger $logger,
        protected array $config,
    ) {
    }

    public function __invoke(Message $message): void
    {
        $payload = json_decode($message->getPayload()['foo'], true);

        if ($payload !== null && isset($payload['userUuid'])) {
            $this->logger->info("message: " . $payload['userUuid']);
            $this->args = $payload;

            try {
                $this->perform();
            } catch (Exception $exception) {
                $this->logger->error("message: " . $exception->getMessage());
            }
        }
    }

    public function perform(): void
    {
        $this->sendWelcomeMail();
    }

    public function sendWelcomeMail(): bool
    {
        $user = $this->userRepository->find($this->args['userUuid']);
        $this->mailService->getMessage()->addTo($user->getEmail(), $user->getName());
        $this->mailService->setSubject('Welcome to ' . $this->config['application']['name']);
        $body = $this->templateRenderer->render('notification-email::welcome', [
            'user'   => $user,
            'config' => $this->config,
        ]);

        $this->mailService->setBody($body);

        try {
            return $this->mailService->send()->isValid();
        } catch (MailException | TransportExceptionInterface $exception) {
            $this->logger->notice($exception->getMessage());
        }

        return false;
    }
}
