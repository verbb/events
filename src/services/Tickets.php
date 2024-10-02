<?php
namespace verbb\events\services;

use verbb\events\Events;
use verbb\events\elements\PurchasedTicket;
use verbb\events\elements\Ticket;

use Craft;
use craft\helpers\Assets;

use yii\base\Component;

use craft\commerce\events\MailEvent;

use Throwable;

class Tickets extends Component
{
    // Properties
    // =========================================================================

    private array $_pdfPaths = [];

    
    // Public Methods
    // =========================================================================

    public function getTicketById(int $ticketId, int $siteId = null): ?Ticket
    {
        return Craft::$app->getElements()->getElementById($ticketId, Ticket::class, $siteId);
    }

    public function onBeforeSendEmail(MailEvent $event): void
    {
        $order = $event->order;
        $commerceEmail = $event->commerceEmail;

        $settings = Events::$plugin->getSettings();

        try {
            // Don't proceed further if there's no voucher in this order
            $hasVoucher = false;

            foreach ($order->lineItems as $lineItem) {
                if (is_a($lineItem->purchasable, Ticket::class)) {
                    $hasVoucher = true;

                    break;
                }
            }

            // No voucher in the order?
            if (!$hasVoucher) {
                return;
            }

            // Check this is an email we want to attach the voucher PDF to
            $matchedEmail = $settings->attachPdfToEmails[$commerceEmail->uid] ?? null;

            if (!$matchedEmail) {
                return;
            }

            $purchasedTickets = PurchasedTicket::find()->orderId($order->id)->all();

            // Generate the PDF for the order
            $pdf = Events::$plugin->getPdf()->renderPdf($purchasedTickets, $order);

            if (!$pdf) {
                return;
            }

            // Save it in a temp location, so we can attach it
            $pdfPath = Assets::tempFilePath('pdf');
            file_put_contents($pdfPath, $pdf);

            // Generate the filename correctly.
            $filenameFormat = $settings->ticketPdfFilenameFormat;
            $fileName = Craft::$app->getView()->renderObjectTemplate($filenameFormat, $order);

            if (!$fileName) {
                if ($order) {
                    $fileName = 'Ticket-' . $order->number;
                } else {
                    $fileName = 'Ticket';
                }
            }

            if (!$pdfPath) {
                return;
            }

            $craftEmail = $event->craftEmail;
            $event->craftEmail->attach($pdfPath, ['fileName' => $fileName . '.pdf', 'contentType' => 'application/pdf']);

            // Store for later
            $this->_pdfPaths[] = $pdfPath;
        } catch (Throwable $e) {
            $error = Craft::t('events', 'PDF unable to be attached to “{email}” for order “{order}”. Error: {error} {file}:{line}', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'email' => $commerceEmail->name,
                'order' => $order->getShortNumber(),
            ]);

            Events::error($error);
        }
    }

    public function onAfterSendEmail(MailEvent $event): void
    {
        // Clear out any generated PDFs
        foreach ($this->_pdfPaths as $pdfPath) {
            unlink($pdfPath);
        }
    }
}
