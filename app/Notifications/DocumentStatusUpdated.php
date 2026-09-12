<?php

namespace App\Notifications;

use App\Models\DocumentStatus;
use App\Models\NotificationType;
use App\Models\StudentDocument;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class DocumentStatusUpdated extends Notification
{
    use Queueable;

    public function __construct(public StudentDocument $document) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $this->document->loadMissing(['documentType', 'returnReason']);
        $documentName = $this->document->documentType?->name ?? str_replace('_', ' ', $this->document->doc_type);
        $documentName = ucwords($documentName ?: 'Document');
        $status = $this->document->status;
        $message = "Your {$documentName} was ".strtolower(DocumentStatus::nameFor($status)).'.';

        if ($status === DocumentStatus::RETURNED && $this->document->returnReason?->name) {
            $message .= ' Reason: '.$this->document->returnReason->name.'.';
        }

        return NotificationType::payload(NotificationType::DOCUMENT_STATUS_UPDATED, [
            'message' => $message,
            'url' => route('student.documents', absolute: false),
        ]);
    }
}
