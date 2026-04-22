<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExtractionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                "slug"        => "invoice",
                "name"        => "Invoice / Factură",
                "description" => "Extrage date din facturi: furnizor, client, număr, dată, produse, totaluri, TVA.",
                "prompt_template" => <<<PROMPT
Ești un expert în procesarea documentelor financiare. Analizează textul următor extras dintr-o factură și returnează EXCLUSIV un obiect JSON valid, fără text suplimentar, fără markdown, fără explicații.

Text factură:
{pdf_text}

Returnează EXACT structura JSON de mai jos (completează câmpurile cu datele găsite sau null dacă lipsesc):
{output_schema}
PROMPT,
                "output_schema" => json_encode([
                    "type" => "object",
                    "properties" => [
                        "invoice_number"   => ["type" => ["string", "null"]],
                        "invoice_date"     => ["type" => ["string", "null"]],
                        "due_date"         => ["type" => ["string", "null"]],
                        "vendor" => [
                            "type" => "object",
                            "properties" => [
                                "name"    => ["type" => ["string", "null"]],
                                "address" => ["type" => ["string", "null"]],
                                "vat_id"  => ["type" => ["string", "null"]],
                            ],
                        ],
                        "client" => [
                            "type" => "object",
                            "properties" => [
                                "name"    => ["type" => ["string", "null"]],
                                "address" => ["type" => ["string", "null"]],
                                "vat_id"  => ["type" => ["string", "null"]],
                            ],
                        ],
                        "line_items" => [
                            "type"  => "array",
                            "items" => [
                                "type" => "object",
                                "properties" => [
                                    "description" => ["type" => ["string", "null"]],
                                    "quantity"    => ["type" => ["number", "null"]],
                                    "unit_price"  => ["type" => ["number", "null"]],
                                    "total"       => ["type" => ["number", "null"]],
                                ],
                            ],
                        ],
                        "subtotal"  => ["type" => ["number", "null"]],
                        "vat_rate"  => ["type" => ["number", "null"]],
                        "vat_amount"=> ["type" => ["number", "null"]],
                        "total"     => ["type" => ["number", "null"]],
                        "currency"  => ["type" => ["string", "null"]],
                    ],
                ]),
                "is_system" => true,
            ],
            [
                "slug"        => "table",
                "name"        => "Table Extractor / Extractor Tabele",
                "description" => "Extrage tabele din PDF-uri și le convertește în format structurat.",
                "prompt_template" => <<<PROMPT
Ești un expert în extragerea datelor tabulare din documente. Analizează textul următor și identifică toate tabelele prezente. Returnează EXCLUSIV un obiect JSON valid, fără text suplimentar.

Text document:
{pdf_text}

Returnează EXACT structura JSON de mai jos:
{output_schema}
PROMPT,
                "output_schema" => json_encode([
                    "type" => "object",
                    "properties" => [
                        "tables" => [
                            "type"  => "array",
                            "items" => [
                                "type" => "object",
                                "properties" => [
                                    "title"   => ["type" => ["string", "null"]],
                                    "headers" => [
                                        "type"  => "array",
                                        "items" => ["type" => "string"],
                                    ],
                                    "rows" => [
                                        "type"  => "array",
                                        "items" => [
                                            "type"  => "array",
                                            "items" => ["type" => ["string", "null"]],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]),
                "is_system" => true,
            ],
            [
                "slug"        => "generic",
                "name"        => "Generic Extractor",
                "description" => "Extrage informații cheie din orice tip de document.",
                "prompt_template" => <<<PROMPT
Ești un expert în analiza documentelor. Analizează textul următor și extrage toate informațiile relevante și structurate. Returnează EXCLUSIV un obiect JSON valid, fără text suplimentar.

Text document:
{pdf_text}

Returnează EXACT structura JSON de mai jos:
{output_schema}
PROMPT,
                "output_schema" => json_encode([
                    "type" => "object",
                    "properties" => [
                        "document_type" => ["type" => ["string", "null"]],
                        "title"         => ["type" => ["string", "null"]],
                        "date"          => ["type" => ["string", "null"]],
                        "entities" => [
                            "type"  => "array",
                            "items" => [
                                "type" => "object",
                                "properties" => [
                                    "type"  => ["type" => ["string", "null"]],
                                    "name"  => ["type" => ["string", "null"]],
                                    "value" => ["type" => ["string", "null"]],
                                ],
                            ],
                        ],
                        "key_values" => [
                            "type" => "object",
                            "additionalProperties" => ["type" => ["string", "null"]],
                        ],
                        "summary" => ["type" => ["string", "null"]],
                    ],
                ]),
                "is_system" => true,
            ],
        ];

        foreach ($templates as $template) {
            DB::table("extraction_templates")->updateOrInsert(
                ["slug" => $template["slug"], "user_id" => null],
                array_merge($template, [
                    "user_id"    => null,
                    "active"     => true,
                    "metadata"   => null,
                    "created_at" => now(),
                    "updated_at" => now(),
                ])
            );
        }
    }
}
