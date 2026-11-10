<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GlobalSubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        collect($this->subjects())->each(function (string $name): void {
            Subject::query()->updateOrCreate(
                ['name' => $name],
                [
                    'code' => $this->codeFor($name),
                    'status' => 'active',
                ],
            );
        });
    }

    /**
     * @return array<int, string>
     */
    private function subjects(): array
    {
        return [
            'Number Work',
            'Letter Work',
            'Phonics',
            'Handwriting',
            'Reading',
            'Storytelling',
            'Rhymes',
            'Practical Life',
            'Sensorial Activities',
            'Health Habits',
            'Social Habits',
            'Physical Development',
            'Creative Activities',
            'Moral Instruction',
            'English Language',
            'Mathematics',
            'Quantitative Reasoning',
            'Verbal Reasoning',
            'Basic Science',
            'Basic Technology',
            'Basic Science and Technology',
            'Information Technology',
            'Computer Studies',
            'Digital Literacy',
            'Social Studies',
            'Civic Education',
            'Security Education',
            'History',
            'Religious and National Values',
            'Christian Religious Studies',
            'Islamic Religious Studies',
            'Yoruba Language',
            'Igbo Language',
            'Hausa Language',
            'French Language',
            'Arabic Language',
            'Cultural and Creative Arts',
            'Music',
            'Fine Art',
            'Visual Arts',
            'Physical and Health Education',
            'Home Economics',
            'Agricultural Science',
            'Prevocational Studies',
            'Business Studies',
            'English Studies',
            'Literature in English',
            'General Mathematics',
            'Further Mathematics',
            'Biology',
            'Chemistry',
            'Physics',
            'Geography',
            'Economics',
            'Government',
            'Commerce',
            'Financial Accounting',
            'Book Keeping',
            'Office Practice',
            'Marketing',
            'Insurance',
            'Entrepreneurship',
            'Data Processing',
            'ICT',
            'Technical Drawing',
            'Building Construction',
            'Woodwork',
            'Metalwork',
            'Auto Mechanics',
            'Electrical Installation and Maintenance',
            'Electronics',
            'Applied Electricity',
            'Food and Nutrition',
            'Home Management',
            'Clothing and Textiles',
            'Garment Making',
            'Dyeing and Bleaching',
            'Cosmetology',
            'Tourism',
            'Animal Husbandry',
            'Fisheries',
            'Catering Craft Practice',
            'Store Management',
            'Salesmanship',
            'Keyboarding',
            'Shorthand',
            'Typewriting',
            'Health Science',
            'Physical Education',
            'Trade Subject',
        ];
    }

    private function codeFor(string $name): string
    {
        return Str::of($name)
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', '_')
            ->trim('_')
            ->limit(40, '')
            ->toString();
    }
}
