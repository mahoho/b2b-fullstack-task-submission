<?php

namespace Database\Seeders;

use App\Entities\Applicant;
use App\Entities\Campaign;
use App\Entities\Partner;
use DateTimeImmutable;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Faker\Factory;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public const DEMO_TOKEN = 'demo-partner-token';

    public const DEMO_CAMPAIGN_ID = '0b8f3c2e-5a61-4c7d-9e2a-3f4b5c6d7e81';

    public const EMPTY_CAMPAIGN_ID = '7d1e9a40-2b3c-4d5e-8f60-718293a4b5c6';

    private const SCORES = [97, 94, 91, 88, 86, 84, 81, 79, 77, 75, 73, 71, 70, 68, 65, 62, 58, 55, 51, 47, 42, 38, null, null];

    private const HIGHLIGHTS = [
        'Led a robotics team to the national finals',
        'Published undergraduate research on renewable microgrids',
        'Fluent in English, Spanish and Mandarin',
        'Volunteers weekly as a maths tutor',
        'Built a budgeting app used by 2,000 students',
        'Top 5% in the national science olympiad',
        'Organised a regional climate hackathon',
        'Two years as a part-time lab assistant',
        'Strong portfolio of data science projects',
        'Captain of the university debate society',
    ];

    public function run(EntityManagerInterface $em): void
    {
        $tokenHash = hash('sha256', self::DEMO_TOKEN);

        if ($em->getRepository(Partner::class)->findOneBy(['apiTokenHash' => $tokenHash]) !== null) {
            $this->command->info('Demo data is already seeded.');

            return;
        }

        $faker = Factory::create();
        $faker->seed(2026);

        $partner = new Partner('Demo University', $tokenHash);
        $campaign = new Campaign($partner, 'Autumn 2026 scholarship', 70, self::DEMO_CAMPAIGN_ID);
        $em->persist($partner);
        $em->persist($campaign);
        $em->persist(new Campaign($partner, 'Spring 2027 exchange', 70, self::EMPTY_CAMPAIGN_ID));

        $firstDay = new DateTimeImmutable('2026-09-01 09:00:00', new DateTimeZone('UTC'));

        foreach (self::SCORES as $day => $score) {
            /** @var list<string> $highlights */
            $highlights = $faker->randomElements(self::HIGHLIGHTS, 4);

            $em->persist(new Applicant(
                $campaign,
                $faker->name(),
                $faker->unique()->safeEmail(),
                $score,
                $highlights,
                $firstDay->modify("+{$day} days"),
            ));
        }

        $em->flush();
    }
}
