<?php
declare(strict_types=1);

/**
 * Matching nama orang (nama lengkap, bisa ada marga/gelar) ke file screenshot
 * (biasanya cuma nama depan/panggilan di filename, contoh:
 *  "Screenshot Aplikasi Fasih Hanna.png" -> harus match ke "Hanna Kristiani Br Ginting").
 */

const MATCHER_STOPWORDS = [
    'screenshot', 'screenshoot', 'ss', 'aplikasi', 'fasih', 'capture',
    'img', 'image', 'photo', 'foto', 'whatsapp', 'wa', 'br', 'boru',
];

function normalize_tokens(string $text): array
{
    $text = strtolower($text);
    $text = preg_replace('/\.[a-z0-9]+$/i', '', $text); // buang extension
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $tokens = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
    return array_values(array_filter($tokens, fn($t) => strlen($t) >= 3));
}

function screenshot_keywords(string $filename): array
{
    $tokens = normalize_tokens($filename);
    $tokens = array_filter($tokens, fn($t) => !in_array($t, MATCHER_STOPWORDS, true));
    return array_values($tokens);
}

/**
 * @param array<int,string> $personNames  index => nama lengkap
 * @param array<int,string> $screenshotFiles  index => nama file (basename)
 * @return array{
 *   matches: array<int,int>,       // personIndex => screenshotIndex
 *   scores: array<int,int>,        // personIndex => skor
 *   unmatchedPersons: array<int>,
 *   unmatchedScreenshots: array<int>
 * }
 */
function match_names_to_screenshots(array $personNames, array $screenshotFiles): array
{
    $personTokens = [];
    foreach ($personNames as $i => $name) {
        $personTokens[$i] = normalize_tokens($name);
    }

    $shotKeywords = [];
    foreach ($screenshotFiles as $i => $fname) {
        $shotKeywords[$i] = screenshot_keywords($fname);
    }

    // Bangun semua kandidat pasangan berskor > 0
    $candidates = []; // [score, personIndex, screenshotIndex, matchedKeywordLen]
    foreach ($personTokens as $pi => $pTokens) {
        foreach ($shotKeywords as $si => $keywords) {
            if (empty($keywords)) {
                continue;
            }
            $score = 0;
            $bestLen = 0;
            foreach ($keywords as $kw) {
                if (in_array($kw, $pTokens, true)) {
                    $score++;
                    $bestLen = max($bestLen, strlen($kw));
                }
            }
            if ($score > 0) {
                $candidates[] = [$score, $pi, $si, $bestLen];
            }
        }
    }

    // Urutkan: skor tertinggi dulu, lalu keyword terpanjang (lebih spesifik/unik)
    usort($candidates, function ($a, $b) {
        if ($a[0] !== $b[0]) return $b[0] <=> $a[0];
        return $b[3] <=> $a[3];
    });

    $matches = [];
    $scores = [];
    $usedPersons = [];
    $usedShots = [];

    foreach ($candidates as [$score, $pi, $si, $len]) {
        if (isset($usedPersons[$pi]) || isset($usedShots[$si])) {
            continue;
        }
        $matches[$pi] = $si;
        $scores[$pi] = $score;
        $usedPersons[$pi] = true;
        $usedShots[$si] = true;
    }

    $unmatchedPersons = array_values(array_diff(array_keys($personNames), array_keys($matches)));
    $unmatchedShots = array_values(array_diff(array_keys($screenshotFiles), array_keys($usedShots)));

    return [
        'matches' => $matches,
        'scores' => $scores,
        'unmatchedPersons' => $unmatchedPersons,
        'unmatchedScreenshots' => $unmatchedShots,
    ];
}
