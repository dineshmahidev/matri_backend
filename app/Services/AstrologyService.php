<?php

namespace App\Services;

use Carbon\Carbon;

class AstrologyService
{
    // Star (Nakshatra) definition with English and Tamil names
    private static $stars = [
        0 => ['en' => 'Ashwini', 'ta' => 'அஸ்வினி'],
        1 => ['en' => 'Bharani', 'ta' => 'பரணி'],
        2 => ['en' => 'Krittika', 'ta' => 'கார்த்திகை'],
        3 => ['en' => 'Rohini', 'ta' => 'ரோகிணி'],
        4 => ['en' => 'Mrigashirsha', 'ta' => 'மிருகசீரிடம்'],
        5 => ['en' => 'Ardra', 'ta' => 'திருவாதிரை'],
        6 => ['en' => 'Punarvasu', 'ta' => 'புனர்பூசம்'],
        7 => ['en' => 'Pushya', 'ta' => 'பூசம்'],
        8 => ['en' => 'Ashlesha', 'ta' => 'ஆயில்யம்'],
        9 => ['en' => 'Magha', 'ta' => 'மகம்'],
        10 => ['en' => 'Purva Phalguni', 'ta' => 'பூரம்'],
        11 => ['en' => 'Uttara Phalguni', 'ta' => 'உத்திரம்'],
        12 => ['en' => 'Hasta', 'ta' => 'அஸ்தம்'],
        13 => ['en' => 'Chitra', 'ta' => 'சித்திரை'],
        14 => ['en' => 'Svati', 'ta' => 'சுவாதி'],
        15 => ['en' => 'Vishakha', 'ta' => 'விசாகம்'],
        16 => ['en' => 'Anuradha', 'ta' => 'அனுஷம்'],
        17 => ['en' => 'Jyeshtha', 'ta' => 'கேட்டை'],
        18 => ['en' => 'Mula', 'ta' => 'மூலம்'],
        19 => ['en' => 'Purva Ashadha', 'ta' => 'பூராடம்'],
        20 => ['en' => 'Uttara Ashadha', 'ta' => 'உத்திராடம்'],
        21 => ['en' => 'Shravana', 'ta' => 'திருவோணம்'],
        22 => ['en' => 'Dhanishta', 'ta' => 'அவிட்டம்'],
        23 => ['en' => 'Shatabhisha', 'ta' => 'சதயம்'],
        24 => ['en' => 'Purva Bhadrapada', 'ta' => 'பூரட்டாதி'],
        25 => ['en' => 'Uttara Bhadrapada', 'ta' => 'உத்திரட்டாதி'],
        26 => ['en' => 'Revati', 'ta' => 'ரேவதி']
    ];

    // Rasi (Moon Sign) definition with English and Tamil names
    private static $rasis = [
        0 => ['en' => 'Mesha (Aries)', 'ta' => 'மேஷம்'],
        1 => ['en' => 'Vrishabha (Taurus)', 'ta' => 'ரிஷபம்'],
        2 => ['en' => 'Mithuna (Gemini)', 'ta' => 'மிதுனம்'],
        3 => ['en' => 'Karka (Cancer)', 'ta' => 'கடகம்'],
        4 => ['en' => 'Simha (Leo)', 'ta' => 'சிம்மம்'],
        5 => ['en' => 'Kanya (Virgo)', 'ta' => 'கன்னி'],
        6 => ['en' => 'Tula (Libra)', 'ta' => 'துலாம்'],
        7 => ['en' => 'Vrishchika (Scorpio)', 'ta' => 'விருச்சிகம்'],
        8 => ['en' => 'Dhanu (Sagittarius)', 'ta' => 'தனுசு'],
        9 => ['en' => 'Makara (Capricorn)', 'ta' => 'மகரம்'],
        10 => ['en' => 'Kumbha (Aquarius)', 'ta' => 'கும்பம்'],
        11 => ['en' => 'Meena (Pisces)', 'ta' => 'மீனம்']
    ];

    // Gana mappings
    private static $ganas = [
        'deva' => [0, 4, 6, 7, 12, 14, 16, 21, 26],
        'manusha' => [1, 3, 5, 10, 11, 13, 19, 20, 24],
        'rakshasa' => [2, 8, 9, 15, 17, 18, 22, 23, 25]
    ];

    // Yoni Animal mappings
    private static $yoniAnimals = [
        0 => 'Horse', 23 => 'Horse',
        1 => 'Elephant', 26 => 'Elephant',
        2 => 'Goat', 7 => 'Goat',
        3 => 'Serpent', 4 => 'Serpent',
        18 => 'Dog',
        6 => 'Cat', 8 => 'Cat',
        9 => 'Rat', 10 => 'Rat',
        11 => 'Cow', 25 => 'Cow',
        12 => 'Buffalo', 14 => 'Buffalo',
        13 => 'Tiger', 15 => 'Tiger',
        16 => 'Hare', 17 => 'Hare',
        19 => 'Monkey', 21 => 'Monkey',
        20 => 'Mongoose',
        22 => 'Lion', 24 => 'Lion'
    ];

    private static $yoniEnemies = [
        'Cow' => 'Tiger', 'Tiger' => 'Cow',
        'Elephant' => 'Lion', 'Lion' => 'Elephant',
        'Horse' => 'Buffalo', 'Buffalo' => 'Horse',
        'Dog' => 'Hare', 'Hare' => 'Dog',
        'Cat' => 'Rat', 'Rat' => 'Cat',
        'Serpent' => 'Mongoose', 'Mongoose' => 'Serpent',
        'Monkey' => 'Goat', 'Goat' => 'Monkey'
    ];

    // Rasi lords mapping
    private static $rasiLords = [
        0 => 'Mars', 7 => 'Mars',
        1 => 'Venus', 6 => 'Venus',
        2 => 'Mercury', 5 => 'Mercury',
        3 => 'Moon',
        4 => 'Sun',
        8 => 'Jupiter', 11 => 'Jupiter',
        9 => 'Saturn', 10 => 'Saturn'
    ];

    // Planetary friendships: [Planet => [Friend1, Friend2...]]
    private static $planetFriends = [
        'Sun' => ['Moon', 'Mars', 'Jupiter'],
        'Moon' => ['Sun', 'Mercury'],
        'Mars' => ['Sun', 'Moon', 'Jupiter'],
        'Mercury' => ['Sun', 'Venus'],
        'Jupiter' => ['Sun', 'Moon', 'Mars'],
        'Venus' => ['Mercury', 'Saturn'],
        'Saturn' => ['Mercury', 'Venus']
    ];

    private static $planetEnemies = [
        'Sun' => ['Venus', 'Saturn'],
        'Moon' => [],
        'Mars' => ['Mercury'],
        'Mercury' => ['Moon'],
        'Jupiter' => ['Mercury', 'Venus'],
        'Venus' => ['Sun', 'Moon'],
        'Saturn' => ['Sun', 'Moon', 'Mars']
    ];

    // Vashya mapping
    private static $vashyaMap = [
        0 => [4, 7],    // Mesha attracts Leo, Scorpio
        1 => [1, 3, 6], // Vrishabha attracts Taurus, Cancer, Libra
        2 => [5],       // Mithuna attracts Virgo
        3 => [7, 8],    // Karka attracts Scorpio, Sag
        4 => [6, 5],    // Simha attracts Libra, Virgo
        5 => [11, 1],   // Kanya attracts Pisces, Taurus
        6 => [9],       // Tula attracts Capricorn
        7 => [3],       // Vrishchika attracts Cancer
        8 => [0],       // Dhanu attracts Aries
        9 => [10, 0],   // Makara attracts Aquarius, Aries
        10 => [0],      // Kumbha attracts Aries
        11 => [9]       // Meena attracts Capricorn
    ];

    // Rajju zones mapping
    private static $rajjuMap = [
        'Siras' => [4, 13, 22],
        'Kanta' => [3, 5, 12, 14, 21, 23],
        'Udara' => [2, 6, 11, 15, 20, 24],
        'Uru' => [1, 7, 10, 16, 19, 25],
        'Pada' => [0, 8, 9, 17, 18, 26]
    ];

    // Vedha pairs
    private static $vedhaPairs = [
        [0, 17], [1, 16], [2, 15], [3, 14], [5, 21], [6, 20],
        [7, 19], [8, 18], [9, 26], [10, 25], [11, 24], [12, 23], [4, 22]
    ];

    /**
     * Calculates the Rasi and Nakshatra based on Date of Birth and Time of Birth.
     * Uses an astronomical approximation of the Nirayana Moon Longitude.
     */
    public function getBirthDetails(string $dob, ?string $tob): array
    {
        // Parse datetime. Default time is 12:00 if not provided
        $timeStr = $tob ? trim($tob) : '12:00';
        
        // Match 12-hour format or 24-hour format
        try {
            $dateTime = Carbon::parse($dob . ' ' . $timeStr);
        } catch (\Exception $e) {
            $dateTime = Carbon::parse($dob . ' 12:00');
        }

        // Days since J2000 epoch (2000-01-01 00:00:00 UTC)
        // Adjust for Indian Standard Time (+5:30)
        $dateTimeUtc = $dateTime->copy()->subMinutes(330);
        $j2000 = Carbon::create(2000, 1, 1, 0, 0, 0, 'UTC');
        
        $diffInSeconds = $dateTimeUtc->diffInSeconds($j2000, false);
        // Note: diffInSeconds is negative if $dateTimeUtc is after $j2000. Laravel's diffInSeconds has a sign if false is passed.
        // Let's get the absolute fractional days
        $days = -$diffInSeconds / 86400.0;

        // Mean Moon longitude at epoch J2000: 201.270 degrees
        // Mean Moon daily motion: 13.176358 degrees/day
        $sayanaLong = (201.270 + $days * 13.176358);
        $sayanaLong = fmod($sayanaLong, 360.0);
        if ($sayanaLong < 0) {
            $sayanaLong += 360.0;
        }

        // Ayanamsa precession: 23.85 degrees at epoch J2000
        // Precession rate: 50.29 seconds per year ~ 0.000137 degrees per day
        $ayanamsa = 23.85 + $days * 0.000137;
        
        // Nirayana (Sidereal) Longitude
        $nirayanaLong = $sayanaLong - $ayanamsa;
        $nirayanaLong = fmod($nirayanaLong, 360.0);
        if ($nirayanaLong < 0) {
            $nirayanaLong += 360.0;
        }

        // Calculate Nakshatra (each Nakshatra is 13.333333 degrees)
        $starIndex = (int) floor($nirayanaLong / (13.333333));
        $starIndex = max(0, min(26, $starIndex));

        // Calculate Rasi (each Rasi is 30 degrees)
        $rasiIndex = (int) floor($nirayanaLong / 30.0);
        $rasiIndex = max(0, min(11, $rasiIndex));

        // Calculate Pada (each Nakshatra has 4 quarters/padas of 3.333333 degrees)
        $starRem = fmod($nirayanaLong, 13.333333);
        $pada = (int) floor($starRem / 3.333333) + 1;
        $pada = max(1, min(4, $pada));

        return [
            'longitude' => round($nirayanaLong, 2),
            'star_index' => $starIndex,
            'star' => self::$stars[$starIndex],
            'rasi_index' => $rasiIndex,
            'rasi' => self::$rasis[$rasiIndex],
            'pada' => $pada,
            'dob' => $dateTime->format('d-m-Y'),
            'tob' => $dateTime->format('h:i A'),
        ];
    }

    /**
     * Build birth details from stored rasi/nakshatram names when DOB/TOB unavailable.
     */
    public function getBirthDetailsFromProfile(string $rasi, string $nakshatram): array
    {
        $rasiIndex = null;
        foreach (self::$rasis as $i => $r) {
            if (strcasecmp($r['en'], $rasi) === 0 || strcasecmp($r['ta'], $rasi) === 0) {
                $rasiIndex = $i;
                break;
            }
        }
        if ($rasiIndex === null) {
            $rasiIndex = (int) $rasi;
        }

        $starIndex = null;
        foreach (self::$stars as $i => $s) {
            if (strcasecmp($s['en'], $nakshatram) === 0 || strcasecmp($s['ta'], $nakshatram) === 0) {
                $starIndex = $i;
                break;
            }
        }
        if ($starIndex === null) {
            $starIndex = (int) $nakshatram;
        }

        return [
            'longitude' => 0,
            'star_index' => $starIndex,
            'star' => self::$stars[$starIndex] ?? ['en' => $nakshatram, 'ta' => $nakshatram],
            'rasi_index' => $rasiIndex,
            'rasi' => self::$rasis[$rasiIndex] ?? ['en' => $rasi, 'ta' => $rasi],
            'pada' => 1,
            'dob' => null,
            'tob' => null,
        ];
    }

    /**
     * Matches two users (Female and Male) and returns a detailed Porutham analysis report.
     */
    public function match(array $femaleDetails, array $maleDetails): array
    {
        $fStar = $femaleDetails['star_index'];
        $mStar = $maleDetails['star_index'];
        $fRasi = $femaleDetails['rasi_index'];
        $mRasi = $maleDetails['rasi_index'];

        $poruthams = [];
        $totalPoints = 0;
        $maxPoints = 10;
        $matchedCount = 0;

        // 1. Dina Porutham
        $dina = $this->checkDina($fStar, $mStar);
        $poruthams['dina'] = $dina;
        $totalPoints += $dina['score'];
        if ($dina['score'] > 0) $matchedCount++;

        // 2. Gana Porutham
        $gana = $this->checkGana($fStar, $mStar);
        $poruthams['gana'] = $gana;
        $totalPoints += $gana['score'];
        if ($gana['score'] > 0.5) $matchedCount++;

        // 3. Mahendra Porutham
        $mahendra = $this->checkMahendra($fStar, $mStar);
        $poruthams['mahendra'] = $mahendra;
        $totalPoints += $mahendra['score'];
        if ($mahendra['score'] > 0) $matchedCount++;

        // 4. Sthree Deergha Porutham
        $sthree = $this->checkSthreeDeergha($fStar, $mStar);
        $poruthams['sthree_deergha'] = $sthree;
        $totalPoints += $sthree['score'];
        if ($sthree['score'] > 0) $matchedCount++;

        // 5. Yoni Porutham
        $yoni = $this->checkYoni($fStar, $mStar);
        $poruthams['yoni'] = $yoni;
        $totalPoints += $yoni['score'];
        if ($yoni['score'] > 0.5) $matchedCount++;

        // 6. Rasi Porutham
        $rasi = $this->checkRasi($fRasi, $mRasi);
        $poruthams['rasi'] = $rasi;
        $totalPoints += $rasi['score'];
        if ($rasi['score'] > 0) $matchedCount++;

        // 7. Rasi Adhipathi Porutham
        $rasiAdhi = $this->checkRasiAdhipathi($fRasi, $mRasi);
        $poruthams['rasi_adhipathi'] = $rasiAdhi;
        $totalPoints += $rasiAdhi['score'];
        if ($rasiAdhi['score'] > 0.5) $matchedCount++;

        // 8. Vasya Porutham
        $vasya = $this->checkVasya($fRasi, $mRasi);
        $poruthams['vasya'] = $vasya;
        $totalPoints += $vasya['score'];
        if ($vasya['score'] > 0) $matchedCount++;

        // 9. Rajju Porutham (Crucial!)
        $rajju = $this->checkRajju($fStar, $mStar);
        $poruthams['rajju'] = $rajju;
        // Rajju is a pass/fail indicator. Usually doesn't add points directly in point-systems, but we mark it.
        $rajjuPass = ($rajju['score'] > 0);

        // 10. Vedha Porutham
        $vedha = $this->checkVedha($fStar, $mStar);
        $poruthams['vedha'] = $vedha;
        $vedhaPass = ($vedha['score'] > 0);

        $compatibilityPercentage = ($totalPoints / $maxPoints) * 100;
        
        // Determine Verdict
        if (!$rajjuPass) {
            $verdict = [
                'en' => 'Not Recommended (Rajju Dosham / Same Rajju)',
                'ta' => 'பொருத்தமில்லை (ரஜ்ஜு தோஷம் / ஒரே ரஜ்ஜு)'
            ];
        } elseif ($matchedCount >= 7) {
            $verdict = [
                'en' => 'Highly Recommended',
                'ta' => 'மிகவும் பொருத்தமானது'
            ];
        } elseif ($matchedCount >= 5) {
            $verdict = [
                'en' => 'Good Match',
                'ta' => 'நல்ல பொருத்தம்'
            ];
        } else {
            $verdict = [
                'en' => 'Average / Not Recommended',
                'ta' => 'சுமாரான பொருத்தம் / பரிந்துரைக்கப்படவில்லை'
            ];
        }

        return [
            'female' => $femaleDetails,
            'male' => $maleDetails,
            'poruthams' => $poruthams,
            'points' => $totalPoints,
            'max_points' => $maxPoints,
            'matched_count' => $matchedCount,
            'compatibility_percentage' => round($compatibilityPercentage, 0),
            'rajju_pass' => $rajjuPass,
            'vedha_pass' => $vedhaPass,
            'verdict' => $verdict
        ];
    }

    private function checkDina(int $fStar, int $mStar): array
    {
        $count = (($mStar - $fStar + 27) % 27) + 1;
        $rem = $count % 9;
        $matched = in_array($rem, [2, 4, 6, 8, 0]);

        return [
            'name_en' => 'Dina Porutham (Health & Longevity)',
            'name_ta' => 'தினப் பொருத்தம் (ஆரோக்கியம் மற்றும் ஆயுள்)',
            'status_en' => $matched ? 'Matched' : 'Not Matched',
            'status_ta' => $matched ? 'பொருத்தம் உண்டு' : 'பொருத்தம் இல்லை',
            'score' => $matched ? 1 : 0,
            'desc_en' => $matched 
                ? 'Excellent compatibility. Promotes good health, prosperity, and a long life for the couple.'
                : 'Poor compatibility. Might lead to minor health issues or mental anxieties.',
            'desc_ta' => $matched
                ? 'மிகச்சிறந்த பொருத்தம். தம்பதியருக்கு நல்ல ஆரோக்கியம், செல்வம் மற்றும் நீண்ட ஆயுளைத் தரும்.'
                : 'பொருத்தம் இல்லை. இது தம்பதியர் இடையே சிறு உடல்நலக் குறைவுகள் அல்லது மனக் கவலைகளை ஏற்படுத்தலாம்.'
        ];
    }

    private function checkGana(int $fStar, int $mStar): array
    {
        $fGana = $this->getGana($fStar);
        $mGana = $this->getGana($mStar);

        $score = 0;
        $matched = false;

        if ($fGana === $mGana) {
            $score = 1;
            $matched = true;
        } elseif ($fGana === 'deva' && $mGana === 'manusha') {
            $score = 1;
            $matched = true;
        } elseif ($fGana === 'manusha' && $mGana === 'deva') {
            $score = 1;
            $matched = true;
        } elseif ($fGana === 'deva' && $mGana === 'rakshasa') {
            $score = 0.5;
            $matched = true;
        } elseif ($fGana === 'rakshasa' && $mGana === 'deva') {
            $score = 0;
            $matched = false;
        } elseif ($fGana === 'manusha' && $mGana === 'rakshasa') {
            $score = 0;
            $matched = false;
        }

        $ganaNames = [
            'deva' => ['en' => 'Divine (Deva)', 'ta' => 'தெய்வ குணம் (தேவ)'],
            'manusha' => ['en' => 'Human (Manusha)', 'ta' => 'மனித குணம் (மனுஷ)'],
            'rakshasa' => ['en' => 'Demon (Rakshasa)', 'ta' => 'ராட்சச குணம் (ராட்சச)']
        ];

        return [
            'name_en' => 'Gana Porutham (Temperament)',
            'name_ta' => 'கணப் பொருத்தம் (மனக்குணம்)',
            'status_en' => $score === 1 ? 'Matched' : ($score === 0.5 ? 'Average Match' : 'Not Matched'),
            'status_ta' => $score === 1 ? 'பொருத்தம் உண்டு' : ($score === 0.5 ? 'சுமார் பொருத்தம்' : 'பொருத்தம் இல்லை'),
            'score' => $score,
            'desc_en' => "Bride is {$ganaNames[$fGana]['en']} and Groom is {$ganaNames[$mGana]['en']}. " . ($score >= 0.5 
                ? 'Good sync in temperament and mutual understanding.' 
                : 'Differences in temperament. Needs patience and adjustments.'),
            'desc_ta' => "பெண் {$ganaNames[$fGana]['ta']}, பிள்ளை {$ganaNames[$mGana]['ta']}. " . ($score >= 0.5 
                ? 'இருவரிடையே குணம் மற்றும் பரஸ்பர புரிந்துணர்வில் நல்ல ஒத்திசைவு இருக்கும்.' 
                : 'மனக்குணங்களில் வேறுபாடு உள்ளது. தம்பதியர் இடையே சகிப்புத்தன்மை மற்றும் அனுசரிப்பு தேவைப்படும்.')
        ];
    }

    private function getGana(int $star): string
    {
        if (in_array($star, self::$ganas['deva'])) return 'deva';
        if (in_array($star, self::$ganas['manusha'])) return 'manusha';
        return 'rakshasa';
    }

    private function checkMahendra(int $fStar, int $mStar): array
    {
        $count = (($mStar - $fStar + 27) % 27) + 1;
        $matched = in_array($count, [4, 7, 10, 13, 16, 19, 22, 25]);

        return [
            'name_en' => 'Mahendra Porutham (Children & Wealth)',
            'name_ta' => 'மகேந்திர பொருத்தம் (புத்திர பாக்கியம்)',
            'status_en' => $matched ? 'Matched' : 'Not Matched',
            'status_ta' => $matched ? 'பொருத்தம் உண்டு' : 'பொருத்தம் இல்லை',
            'score' => $matched ? 1 : 0,
            'desc_en' => $matched 
                ? 'Blessed with healthy progeny, continuous growth, and family lineage.'
                : 'Average offspring or potential delay in having children. Other indicators should be good.',
            'desc_ta' => $matched
                ? 'நல்ல புத்திர பாக்கியம் மற்றும் வம்ச விருத்தியைத் தரும்.'
                : 'குழந்தைப் பேறில் தாமதம் ஏற்படலாம் அல்லது சுமாரான பலன் தரும்.'
        ];
    }

    private function checkSthreeDeergha(int $fStar, int $mStar): array
    {
        $count = (($mStar - $fStar + 27) % 27) + 1;
        $matched = $count > 13;

        return [
            'name_en' => 'Sthree Deergha Porutham (Wife\'s Prosperity)',
            'name_ta' => 'ஸ்திரீ தீர்க்க பொருத்தம் (மங்கல வாழ்வு)',
            'status_en' => $matched ? 'Matched' : 'Not Matched',
            'status_ta' => $matched ? 'பொருத்தம் உண்டு' : 'பொருத்தம் இல்லை',
            'score' => $matched ? 1 : 0,
            'desc_en' => $matched 
                ? 'Ensures long-term wealth, happiness, and a prosperous life for the wife.'
                : 'Average match. Distance between birth stars is close.',
            'desc_ta' => $matched
                ? 'மனைவிக்கு நீண்ட சுமங்கலி பாக்கியம், மகிழ்ச்சி மற்றும் குடும்பத்தில் செல்வம் தரும்.'
                : 'சுமாரான பொருத்தம். நட்சத்திர இடைவெளி குறைவாக உள்ளது.'
        ];
    }

    private function checkYoni(int $fStar, int $mStar): array
    {
        $fAnimal = self::$yoniAnimals[$fStar] ?? 'Other';
        $mAnimal = self::$yoniAnimals[$mStar] ?? 'Other';

        $score = 0.5;
        $matched = true;

        if ($fAnimal === $mAnimal) {
            $score = 1;
        } elseif (isset(self::$yoniEnemies[$fAnimal]) && self::$yoniEnemies[$fAnimal] === $mAnimal) {
            $score = 0;
            $matched = false;
        }

        return [
            'name_en' => 'Yoni Porutham (Physical Compatibility)',
            'name_ta' => 'யோனி பொருத்தம் (உடல் பொருத்தம்)',
            'status_en' => $score === 1 ? 'Excellent' : ($score === 0 ? 'Not Matched' : 'Matched'),
            'status_ta' => $score === 1 ? 'மிகச் சிறப்பு' : ($score === 0 ? 'பொருத்தமில்லை' : 'பொருத்தம் உண்டு'),
            'score' => $score,
            'desc_en' => "Bride Animal: {$fAnimal}, Groom Animal: {$mAnimal}. " . ($score === 0 
                ? 'Hostile compatibility. Can lead to lack of physical harmony and disputes.' 
                : 'Harmonious physical compatibility and mutual affection.'),
            'desc_ta' => "பெண் யோனி: {$fAnimal}, ஆண் யோனி: {$mAnimal}. " . ($score === 0 
                ? 'பகை யோனி பொருத்தம். இது தம்பதியர் இடையே உடல் ரீதியான ஒத்திசைவின்மை மற்றும் சண்டைகளை உருவாக்கலாம்.' 
                : 'தம்பதியர் இடையே நல்ல உடற்கூறு ஒத்திசைவு மற்றும் தாம்பத்திய சுகம் தரும்.')
        ];
    }

    private function checkRasi(int $fRasi, int $mRasi): array
    {
        $count = (($mRasi - $fRasi + 12) % 12) + 1;
        
        // Good: 7 (Sama Sapthaka), 12, Same Rasi (1) under good conditions. 9, 10, 11
        $matched = in_array($count, [1, 7, 9, 10, 11, 12]);
        if ($count === 6 || $count === 8) {
            $matched = false; // Shastastaka (Very bad)
        }

        return [
            'name_en' => 'Rasi Porutham (Family & Lineage)',
            'name_ta' => 'ராசிப் பொருத்தம் (வம்ச வளர்ச்சி)',
            'status_en' => $matched ? 'Matched' : 'Not Matched',
            'status_ta' => $matched ? 'பொருத்தம் உண்டு' : 'பொருத்தம் இல்லை',
            'score' => $matched ? 1 : 0,
            'desc_en' => $matched 
                ? 'Promotes unity, family bonding, and overall happiness for the couple\'s household.'
                : 'Rasi counts indicate potential difference of opinion or family disputes (Shastastaka/Dwirdwadasa).',
            'desc_ta' => $matched
                ? 'குடும்ப ஒற்றுமை, மகிழ்ச்சி மற்றும் வம்ச விருத்திக்கு வழிவகுக்கும்.'
                : 'குடும்பத்தினரிடையே கருத்து வேறுபாடு அல்லது சச்சரவுகளை ஏற்படுத்தலாம் (சஷ்டாஷ்டக தோஷம்).'
        ];
    }

    private function checkRasiAdhipathi(int $fRasi, int $mRasi): array
    {
        $fLord = self::$rasiLords[$fRasi];
        $mLord = self::$rasiLords[$mRasi];

        $score = 0.5;
        if ($fLord === $mLord) {
            $score = 1;
        } else {
            $fFriends = self::$planetFriends[$fLord] ?? [];
            $mFriends = self::$planetFriends[$mLord] ?? [];
            $fEnemies = self::$planetEnemies[$fLord] ?? [];
            $mEnemies = self::$planetEnemies[$mLord] ?? [];

            $isFFriendly = in_array($mLord, $fFriends);
            $isMFriendly = in_array($fLord, $mFriends);
            $isFEnemy = in_array($mLord, $fEnemies);
            $isMEnemy = in_array($fLord, $mEnemies);

            if ($isFFriendly && $isMFriendly) {
                $score = 1;
            } elseif ($isFEnemy || $isMEnemy) {
                $score = 0;
            }
        }

        return [
            'name_en' => 'Rasi Adhipathi Porutham (Ruler Compatibility)',
            'name_ta' => 'ராசி அதிபதி பொருத்தம் (நட்பு குணம்)',
            'status_en' => $score === 1 ? 'Matched' : ($score === 0.5 ? 'Average' : 'Not Matched'),
            'status_ta' => $score === 1 ? 'பொருத்தம் உண்டு' : ($score === 0.5 ? 'சுமார் பொருத்தம்' : 'பொருத்தம் இல்லை'),
            'score' => $score,
            'desc_en' => "Bride Lord: {$fLord}, Groom Lord: {$mLord}. " . ($score >= 0.5 
                ? 'Friendly lords. Ensures friendly relationship and cooperation between families.' 
                : 'Incompatible planet rulers. Might lead to ego clashes.'),
            'desc_ta' => "பெண் ராசி அதிபதி: {$fLord}, ஆண் ராசி அதிபதி: {$mLord}. " . ($score >= 0.5 
                ? 'ராசி அதிபதிகள் நட்பு உடையவர்கள். இது தம்பதியர் இடையே அன்யோன்யம் மற்றும் நல்ல தோழமையை வளர்க்கும்.' 
                : 'அதிபதிகள் பகை உடையவர்கள். தம்பதியருக்குள் ஈகோ மற்றும் கருத்து வேறுபாடுகள் வரலாம்.')
        ];
    }

    private function checkVasya(int $fRasi, int $mRasi): array
    {
        $vashyaList = self::$vashyaMap[$fRasi] ?? [];
        $matched = in_array($mRasi, $vashyaList);

        return [
            'name_en' => 'Vasya Porutham (Mutual Attraction)',
            'name_ta' => 'வசிய பொருத்தம் (அன்பு மற்றும் ஈர்ப்பு)',
            'status_en' => $matched ? 'Matched' : 'Not Matched',
            'status_ta' => $matched ? 'பொருத்தம் உண்டு' : 'பொருத்தம் இல்லை',
            'score' => $matched ? 1 : 0,
            'desc_en' => $matched 
                ? 'Strong magnetic attraction and deep love between partners.'
                : 'Moderate attraction. Mutual respect will guide the love.',
            'desc_ta' => $matched
                ? 'இருவரிடையே காந்தம் போன்ற ஈர்ப்பும், பிரியமும், பிரிக்க முடியாத அன்பும் இருக்கும்.'
                : 'சாதாரண ஈர்ப்பு. பரஸ்பர மரியாதை அன்பை வளர்க்கும்.'
        ];
    }

    private function checkRajju(int $fStar, int $mStar): array
    {
        $fRajju = $this->getRajjuZone($fStar);
        $mRajju = $this->getRajjuZone($mStar);

        $matched = ($fRajju !== $mRajju);

        $rajjuNames = [
            'Siras' => ['en' => 'Siras (Head)', 'ta' => 'சிரசு (தலை)'],
            'Kanta' => ['en' => 'Kanta (Neck)', 'ta' => 'கண்டம் (கழுத்து)'],
            'Udara' => ['en' => 'Udara (Stomach)', 'ta' => 'உதரம் (வயிறு)'],
            'Uru' => ['en' => 'Uru (Thigh)', 'ta' => 'ஊரு (தொடை)'],
            'Pada' => ['en' => 'Pada (Foot)', 'ta' => 'பாதம் (பாதம்)']
        ];

        return [
            'name_en' => 'Rajju Porutham (Husband\'s Longevity)',
            'name_ta' => 'ரஜ்ஜு பொருத்தம் (மாங்கல்ய பாக்கியம்)',
            'status_en' => $matched ? 'Matched' : 'Rajju Dosham (Not Matched)',
            'status_ta' => $matched ? 'பொருத்தம் உண்டு' : 'ரஜ்ஜு தோஷம் (பொருந்தவில்லை)',
            'score' => $matched ? 1 : 0,
            'desc_en' => "Bride Rajju: {$rajjuNames[$fRajju]['en']}, Groom Rajju: {$rajjuNames[$mRajju]['en']}. " . ($matched 
                ? 'Safe. Ensures husband\'s longevity and continuous marital bliss.' 
                : 'WARNING: Same Rajju. Fails. Highly risk to husband\'s health/longevity according to classical astrology.'),
            'desc_ta' => "பெண் ரஜ்ஜு: {$rajjuNames[$fRajju]['ta']}, ஆண் ரஜ்ஜு: {$rajjuNames[$mRajju]['ta']}. " . ($matched 
                ? 'சுப பலன். கணவருக்கு தீர்க்க ஆயுளையும், தம்பதியருக்கு தீர்க்க சுமங்கலி யோகத்தையும் தரும்.' 
                : 'எச்சரிக்கை: ஒரே ரஜ்ஜு. மாங்கல்ய தோஷம் உள்ளது. இது திருமணத்திற்கு பரிந்துரைக்கப்படுவதில்லை.')
        ];
    }

    private function getRajjuZone(int $star): string
    {
        foreach (self::$rajjuMap as $zone => $stars) {
            if (in_array($star, $stars)) return $zone;
        }
        return 'Pada';
    }

    private function checkVedha(int $fStar, int $mStar): array
    {
        $hasVedha = false;
        foreach (self::$vedhaPairs as $pair) {
            if (($pair[0] === $fStar && $pair[1] === $mStar) || ($pair[1] === $fStar && $pair[0] === $mStar)) {
                $hasVedha = true;
                break;
            }
        }

        $matched = !$hasVedha;

        return [
            'name_en' => 'Vedha Porutham (Affliction Avoidance)',
            'name_ta' => 'வேதை பொருத்தம் (பகை நீக்கம்)',
            'status_en' => $matched ? 'Matched' : 'Vedha Dosham (Not Matched)',
            'status_ta' => $matched ? 'பொருத்தம் உண்டு' : 'வேதை தோஷம் (பொருந்தவில்லை)',
            'score' => $matched ? 1 : 0,
            'desc_en' => $matched 
                ? 'No affliction. Minimal conflicts and smooth relationship.'
                : 'Affliction present. Mutual stars are in conflict. Might lead to arguments.',
            'desc_ta' => $matched
                ? 'வேதை தோஷம் இல்லை. தம்பதியர் சச்சரவுகள் இல்லாமல் வாழ்வார்கள்.'
                : 'நட்சத்திர பகை உள்ளது. தம்பதியருக்குள் அடிக்கடி சண்டைகளும் மனஸ்தாபங்களும் வரலாம்.'
        ];
    }
}
