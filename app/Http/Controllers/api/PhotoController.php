<?php

namespace App\Http\Controllers\api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller as Controller;
use App\Photo as Photo;
use Illuminate\Support\Facades\DB;

use Google\Cloud\Vision\VisionClient;

class PhotoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
        $photos = DB::table('photos')->where('device_id', '=', $request->input('device_id'))->paginate(15);
        echo json_encode($photos);
    }


    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $file = $request->photo;
        $path = $file->store('images');

        $photo = new Photo();
        $photo->url = $path;
        $photo->name = $file->getClientOriginalName();
        $photo->device_id = $request->input('device_id');

        $photo->save();

        $vision = new VisionClient([
            'keyFilePath' => './visualscout-1071723ca679.json',
            'projectId' => 'primeval-falcon-145807'
        ]);

        // Annotate an image, detecting faces.
        $image = $vision->image(
            fopen($file->getRealPath(), 'r'),
            ['TYPE_UNSPECIFIED']
        );

        $annotation = $vision->annotate($image);

        dd($annotation);

// Determine if the detected faces have headwear.
        foreach ($annotation->faces() as $key => $face) {
            if ($face->hasHeadwear()) {
                echo "Face $key has headwear.\n";
            }
        }

        echo json_encode([
            'success' => 'ok',
            'data' => $photo
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
        $photo = Photo::find($id);
        if( isset($photo) ) {
            $photo->delete();
        }
        echo json_encode([
            'success' => 'ok'
        ]);

    }
}
