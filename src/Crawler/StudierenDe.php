<?php
namespace Jungwild\Crawler;

use Jungwild\Crawler;
use Jungwild\Datasets\School;

class StudierenDe extends Crawler
{
    private $school_listing_page_count;
    private $support_types;

    public function run() {

        /*
         * ermittle anzahl Seiten von hochschulen
         */
        $this->scrapeListingCount();

        /*
         * scrape alle listings
         */
        $this->scrapeAllSchoolListings();

    }


    private function scrapeAllSchoolListings() {

        for ($i=1;$i<=$this->school_listing_page_count;$i++) {

            if($dom = $this->get('https://studieren.de/hochschulliste.t-0.s-'.$i.'.html')) {

                $this->scrapeSchoolListing($dom);

            }

        }

    }

    /*
     * crawle anzahl der Listing Seiten
     */
    private function scrapeListingCount() {

        if($dom = $this->get('https://studieren.de/hochschulliste.0.html')) {

            $this->school_listing_page_count = (int)$dom->filter('a[title="Letzte Seite"].last')->first()->safeText();
        }

        return false;

    }

    /*
     * crawle Listing Seite der Hochschulen
     */
    private function scrapeSchoolListing($dom) {

        $dom->filter('table.academyListing tr')->each(function($dom){

            if($dom->filter('td.info')->count() > 0) {

                $school = new School();

                $title = $dom->filter('td.title > b > a');
                $school->name = $title->safeText();

                $school->city = $dom->filter('td.title > a')->eq(1)->safeText();
                $school->zip = $dom->filter('td.title > a')->eq(0)->safeText();

                /*
                 * default uni
                 */
                $school->schooltype_id = 1;

                /*
                 * typ FH ?
                 */
                if($dom->filter('.icon > img[src="typo3conf/ext/as_searchengine/res/gfx/filter/filter_university_of_applied_sciences_16x16.gif"]')->count() > 0) {
                    $school->schooltype_id = 2;
                }

                /*
                 * typ Akademie ?
                 */
                else if($dom->filter('.icon > img[src="typo3conf/ext/as_searchengine/res/gfx/filter/filter_university_of_cooperative_education_16x16.gif"]')->count() > 0) {
                    $school->schooltype_id = 3;
                }


                $this->scrapeSchool($title->safeAttr('href'), $school);

            }



        });

    }

    /*
     * crawle Hochschulseite
     */
    private function scrapeSchool($url, $school) {

        $this->info('crawle ' . $school->name);

        $url = 'https://studieren.de/' . $url;

        if($dom = $this->get($url)) {

            /*
             * LAND
             */
            $school->country = 'DE';


            /*
             * Bundesland
             */
            $school->state = $dom->filter('.academyAddress td:contains("Bundesland") + td.data')->first()->safeText();
            $school->state = $this->stateToCode($school->state);

            /*
             * Straße Hausnummer
             */
            $str = $dom->filter('.academyAddress td:contains("Sekretariat") + td.data')->first()->safeText();

            if($strnum = $this->seperateStreetNumber($str)) {
                $school->street = $strnum['street'];
                $school->number = $strnum['number'];
            }
            else {
                $school->street = $str->safeText();
            }

            /*
             * Telefon
             */
            $school->phone = $dom->filter('.academyAddress td:contains("Telefon") + td.data')->first()->safeText();

            /*
             * Fax
             */
            $school->fax = $dom->filter('.academyAddress td:contains("Fax") + td.data')->first()->safeText();

            /*
             * E-Mail
             */
            $school->email = $dom->filter('.academyAddress td:contains("E-Mail") + td.data')->first()->safeText();

            /*
             * Trägerschaft
             */
            $support = $dom->filter('.academyAddress td:contains("Trägerschaft") + td.data')->first()->safeText();

            $school->support_id = $this->addOrGetSupport($support);

            /*
             * Homepage
             */
            $school->web = $dom->filter('.academyAddress td.label:contains("Internet") + td.data')->first()->safeText();

            /*
            * Studenten
            */
            $school->student_count = $dom->filter('.academyAddress td.label:contains("Studenten") + td.data')->first()->safeText();

            /*
             * Beschreibung
             */
            $school->description = $dom->filter('.academyAddress > div.data')->first()->safeText();


            if($school_id = $this->saveSchool($school)) {

                $this->scrapeSubjectListing($dom, $school_id);

            }
            else {
                $this->error('konnte schule nicht speichern => ' . $url);
            }

        }
    }

    /*
     * crawle Studiengänge der Hochschuleseite
     */
    private function scrapeSubjectListing($dom, $school_id) {

        $i=0;
        $dom->filter('.academyStudyCourseListing td.title')->each(function($title) use ($school_id, &$i){

            $this->saveSubject($title->safeText(), $school_id);
            $i++;

        });

        $this->info($i . ' Studiengänge gespeichert!');

    }

    private function addOrGetSupport($support) {

        if(!$this->support_types) {

            if($supports = $this->api->get('school/supports')) {
                foreach ($supports as $s) {

                    $this->support_types[$s['name']] = $s['id'];

                }
            }

        }

        if(!isset($this->support_types[$support])) {

            if($sup = $this->api->post('school/support',['name' => $support])) {

                $this->support_types[$support] = $sup['id'];
                return $sup['id'];

            }
        }

        return $this->support_types[$support];

    }

    /*
     * Fach speichern
     */
    private function saveSubject($subject, $school_id) {
        if($s = $this->api->post('school/subject', [
            'name' => $subject,
            'school_id' => $school_id
        ])) {

            return true;
        }

        return false;
    }

    /*
     * Schule speichern
     */
    private function saveSchool($school) {

        if($s = $this->api->post('school', $school->toArray())) {

            return $s['id'];
        }

        return false;

    }

    /*
     * wandes Bundesländer in ISO COde um
     */
    private function stateToCode($state) {

        $states = [
            'Baden-Württemberg' => 'BW',
            'Bayern' => 'BY',
            'Berlin' => 'BE',
            'Brandenburg' => 'BB',
            'Bremen' => 'HB',
            'Hamburg' => 'HH',
            'Hessen' => 'HE',
            'Mecklenburg-Vorpommern' => 'MV',
            'Niedersachsen' => 'NI',
            'Nordrhein-Westfalen' => 'NW',
            'Rheinland-Pfalz' => 'RP',
            'Saarland' => 'SL',
            'Sachsen' => 'SN',
            'Sachsen-Anhalt' => 'ST',
            'Schleswig-Holstein' => 'SH',
            'Thüringen' => 'TH'
        ];

        if(!isset($states[$state])) {
            $this->error('Unbekanntest Bundesland: ' . $state);

            return null;
        }

        return $states[$state];
    }


}
