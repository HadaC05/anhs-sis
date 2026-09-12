<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    /**
     * Seed the application's document types table.
     */
    public function run(): void
    {
        foreach (DocumentType::definitions() as $type) {
            DocumentType::query()->updateOrCreate(
                ['slug' => $type['slug']],
                [
                    'name' => $type['name'],
                    'description' => $type['description'],
                    'is_required' => $type['is_required'],
                    'sort_order' => $type['sort_order'],
                ],
            );
        }

        DocumentType::clearOptionsCache();
    }
}
