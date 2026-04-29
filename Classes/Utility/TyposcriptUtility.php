<?php
namespace JVelletti\Jvchat\Utility;

use TYPO3\CMS\Core\TypoScript\ExtendedTemplateService;

class TyposcriptUtility
{

    public static function loadTypoScriptviaCurl($path )
    {
        $url = trim((string) $path) ;
        $curl = curl_init();

        curl_setopt_array($curl,
            [   CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'GET'
            ]);

        $response = curl_exec($curl);

        curl_close($curl);
        if( $response ) {
            return json_decode( $response , true ) ;
        }
        return false ;
    }
    public static function loadTypoScriptFromRequest($request, $extKey = '', $getConstants = false , $pid = 0 )
    {
        if ( $request && $pid > 0 &&  method_exists($request , "getAttribute") && $request->getAttribute('frontend.typoscript')) {
            $ts = ($request->getAttribute('frontend.typoscript')->hasSetup() ? $request->getAttribute('frontend.typoscript')->getSetupArray() : false );
        } else {
            return [];
        }

        if ($getConstants) {
            // Todo get Constants  is untestet
            if (!empty($extKey)) {
                $ts = self::removeDotsFromTypoScriptArray($ts['config.'][$extKey . '.']);
            } else {
                $ts = self::removeDotsFromTypoScriptArray($ts['config.']);
            }
        } else {
            if (!empty($extKey)) {
                $ts = self::removeDotsFromTypoScriptArray($ts['plugin.'][$extKey . '.']);
            } else {
                $ts = self::removeDotsFromTypoScriptArray($ts['plugin.']);
            }
        }
        return $ts;
    }

    /**
     * Removes the dots from an typoscript array
     * @param $array
     * @return array
     * @author Peter Benke <pbenke@allplan.com>
     */
    private static function removeDotsFromTypoScriptArray($array)
    {

        $newArray = [];

        if (is_array($array)) {

            foreach ($array as $key => $val) {

                if (is_array($val)) {

                    // Remove last character (dot)
                    $newKey = substr($key, 0, -1);
                    $newVal = self::removeDotsFromTypoScriptArray($val);

                } else {

                    $newKey = $key;
                    $newVal = $val;

                }

                $newArray[$newKey] = $newVal;

            }

        }

        return $newArray;

    }

}