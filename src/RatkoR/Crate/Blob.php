<?php

namespace RatkoR\Crate;

class Blob
{
    /**
     * Uploads given file to Crate.
     *
     * @param  string $path Path to file that will be uploaded
     * @return string       Digest hash if upload was successfull, false otherwise
     */
    public static function upload($path, $table = 'myblobs')
    {
        // TODO:
        //  - calculate digest hash
        //  - get crate server IP from config
        //  - upload to crate as:
        //        curl -isSX PUT '127.0.0.1:4200/_blobs/myblobs/4a756ca07e9487f482465a99e8286abc86ba4dc7' -d 'contents'
        return "4a756ca07e9487f482465a99e8286abc86ba4dc7";
    }

    /**
     * Downloads file with given digest hash
     *
     * @šaram string  $hash Digest hash of the file that needs to be downloaded
     */
    public static function download($hash)
    {
        // TODO:
        //  - get crate server IP from config
        //  - download from crate as:
        //        curl -sS '127.0.0.1:4200/_blobs/myblobs/4a756ca07e9487f482465a99e8286abc86ba4dc7'
        return null;
    }

}
