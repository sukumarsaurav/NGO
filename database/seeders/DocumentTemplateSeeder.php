<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\DocumentTemplate;
use Illuminate\Database\Seeder;

/**
 * One default, active template per document type — IssueDocument falls back
 * to `is_default` when no explicit template is passed. See
 * docs/03-ROADMAP.md's Sprint 4 spec.
 */
class DocumentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        DocumentTemplate::query()->firstOrCreate(
            ['type' => 'id_card', 'is_default' => true],
            [
                'name' => 'Default ID card',
                'body_html' => $this->idCardHtml(),
                'css' => $this->idCardCss(),
                'page_size' => 'CR80',
                'orientation' => 'landscape',
                'qr_enabled' => true,
                'qr_position' => ['x' => 62, 'y' => 32, 'size' => 18],
                'is_active' => true,
            ]
        );

        DocumentTemplate::query()->firstOrCreate(
            ['type' => 'appointment_letter', 'is_default' => true],
            [
                'name' => 'Default appointment letter',
                'body_html' => $this->appointmentLetterHtml(),
                'css' => $this->letterCss(),
                'page_size' => 'A4',
                'orientation' => 'portrait',
                'qr_enabled' => true,
                'qr_position' => ['x' => 170, 'y' => 270, 'size' => 25],
                'is_active' => true,
            ]
        );

        DocumentTemplate::query()->firstOrCreate(
            ['type' => 'certificate', 'is_default' => true],
            [
                'name' => 'Default certificate',
                'body_html' => $this->certificateHtml(),
                'css' => $this->certificateCss(),
                'page_size' => 'A4',
                'orientation' => 'landscape',
                'qr_enabled' => true,
                'qr_position' => ['x' => 255, 'y' => 175, 'size' => 25],
                'is_active' => true,
            ]
        );
    }

    private function idCardHtml(): string
    {
        return <<<'HTML'
            <div class="card">
                <div class="card-header">{{ config('app.name') }}</div>
                <div class="card-body">
                    <div class="card-name">{{ $member->name }}</div>
                    <div class="card-meta">{{ $member->member_code }}</div>
                    <div class="card-meta">{{ $member->designation ?? 'Member' }}</div>
                    <div class="card-meta">{{ $member->department ?? '' }}</div>
                </div>
            </div>
            HTML;
    }

    private function idCardCss(): string
    {
        return <<<'CSS'
            .card { width: 85.6mm; height: 54mm; box-sizing: border-box; padding: 4mm; position: relative; }
            .card-header { font-size: 8pt; font-weight: bold; color: #2f7a4f; }
            .card-body { margin-top: 6mm; }
            .card-name { font-size: 12pt; font-weight: bold; }
            .card-meta { font-size: 9pt; color: #4b5563; margin-top: 2mm; }
            CSS;
    }

    private function appointmentLetterHtml(): string
    {
        return <<<'HTML'
            <div class="letter">
                <div class="letter-header">{{ config('app.name') }}</div>
                <div class="letter-date">{{ $document->issued_on->format('d M Y') }}</div>
                <h1>Letter of Appointment</h1>
                <p>
                    This is to certify that <strong>{{ $member->name }}</strong>
                    (Member code: {{ $member->member_code }}) has been appointed as
                    <strong>{{ $member->designation }}</strong>
                    @if($member->department) in the {{ $member->department }} department @endif.
                </p>
                <p>We look forward to their continued contribution to our mission.</p>
                <div class="letter-footer">Document number: {{ $document->document_number }}</div>
            </div>
            HTML;
    }

    private function letterCss(): string
    {
        return <<<'CSS'
            .letter { padding: 20mm; }
            .letter-header { font-size: 14pt; font-weight: bold; color: #2f7a4f; }
            .letter-date { font-size: 10pt; color: #4b5563; margin-top: 4mm; }
            h1 { font-size: 16pt; margin-top: 12mm; }
            p { font-size: 11pt; line-height: 1.6; }
            .letter-footer { margin-top: 20mm; font-size: 9pt; color: #6b7280; }
            CSS;
    }

    private function certificateHtml(): string
    {
        return <<<'HTML'
            <div class="certificate">
                <div class="cert-org">{{ config('app.name') }}</div>
                <h1>Certificate of Recognition</h1>
                <p class="cert-body">This certifies that</p>
                <div class="cert-name">{{ $member->name }}</div>
                <p class="cert-body">{{ $document->title }}</p>
                <div class="cert-footer">Document number: {{ $document->document_number }} · Issued {{ $document->issued_on->format('d M Y') }}</div>
            </div>
            HTML;
    }

    private function certificateCss(): string
    {
        return <<<'CSS'
            .certificate { padding: 25mm; text-align: center; }
            .cert-org { font-size: 12pt; font-weight: bold; color: #2f7a4f; }
            h1 { font-size: 22pt; margin-top: 15mm; }
            .cert-body { font-size: 12pt; margin-top: 6mm; }
            .cert-name { font-size: 20pt; font-weight: bold; margin-top: 6mm; }
            .cert-footer { margin-top: 25mm; font-size: 9pt; color: #6b7280; }
            CSS;
    }
}
