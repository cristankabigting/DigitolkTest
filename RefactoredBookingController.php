<?php

namespace DTApi\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

use DTApi\Models\Job;
use DTApi\Models\Distance;
use DTApi\Repository\BookingRepository;


class BookingController extends Controller
{

	protected $repository; // protected class

 	public function __construct(BookingRepository $bookingRepository)
    {
        $this->middleware('auth');

        $this->repository = $bookingRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        // I think it is better to deliver the correct response based on authenticated User Role
    	// Also consider that if Business Rule changes this code will easily adapt to changes

        $user  = Auth::user(); // this is the authenticated user;

        $id = Auth::id();	// this is the id of the authenticated user

        // If the authenticated user is ADMIN - you might have a different response for ADMIN
        if( $user->type == env('ADMIN_ROLE_ID') )
        {
        	$response = $this->repository->getAdminJobs($id);	// Method to fetch jobs for ADMIN only
        }

        // If the authenticated user is SUPER ADMIN - you might have a different response for SUPER ADMIN
        if( $user->type == env('SUPERADMIN_ROLE_ID') )
        {
        	$response = $this->repository->getSuperAdminJobs($id);	// Method to fetch jobs for SUPER ADMIN only
        }

        // What we are basically saying here is that the user is NOT an ADMIN and is NOT also a SUPER ADMIN
        if ( $user->type != env('ADMIN_ROLE_ID') AND  $user->type != env('SUPERADMIN_ROLE_ID') )
        {
        	$response = $this->repository->getUsersJobs($id);	// Method to fetch jobs for OTHER USERS
        } 

        return response($response);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
    	$user = Auth::user(); // this is the authenticated user;

    	$data = $request->all();

    	$response = $this->repository->store($user, $data);

    	return response($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
        $job = $this->repository->with('translatorJobRel.user')->find($id);
        return response($job);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        
        $user = Auth::user(); // this is the authenticated user;

        $id = Auth::id();	// this is the id of the authenticated user

        $data = $request->all();

        $response = $this->repository->updateJob($id, array_except($data, ['_token', 'submit']), $user);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function immediateJobEmail(Request $request)
    {
        $adminSenderEmail = config('app.adminemail');

        $data = $request->all();

        $response = $this->repository->storeJobEmail($data);

        return response($response);
    }


    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getHistory(Request $request)
    {

        $user = Auth::user(); // this is the authenticated user;

        $id = Auth::id();	// this is the id of the authenticated user

        // Here we are referring to OTHER USERS
        if ( $user->type != env('ADMIN_ROLE_ID') AND  $user->type != env('SUPERADMIN_ROLE_ID') )
        {
        	$response = $this->repository->getUsersJobsHistory($id, $request);
        }
        else
        {
        	// Jobs are viewed all for ADMIN and SUPER ADMIN	
        	$response = null;
        }

        return response($response);
    }


    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function acceptJob(Request $request)
    {
        $user = Auth::user(); // this is the authenticated user;

        $data = $request->all();

        $response = $this->repository->acceptJob($data, $user);

        return response($response);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function acceptJobWithId(Request $request)
    {
        $user = Auth::user(); // this is the authenticated user;

        //$data = $request->all();

        $jobid = $request->get('job_id');

        $response = $this->repository->acceptJobWithId($jobid, $user);

        return response($response);
    }

    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function cancelJob(Request $request)
    {
		
		$user = Auth::user(); // this is the authenticated user;

        $data = $request->all();

        $response = $this->repository->cancelUserJobAjax($data, $user);

        return response($response);
    }


    /**
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function endJob(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->EndJob($data);

        return response($response);
    }

	/**
	* @param  \Illuminate\Http\Request  $request
	* @return \Illuminate\Http\Response
	*/
   public function customerNotCall(Request $request)
   {
        $data = $request->all();

        $response = $this->repository->customerNotCall($data);

        return response($response);
    }

	/**
	* @param  \Illuminate\Http\Request  $request
	* @return \Illuminate\Http\Response
	*/
    public function getPotentialJobs(Request $request)
    {

		$user = Auth::user(); // this is the authenticated user;
		
        $response = $this->repository->getPotentialJobs($user);

        return response($response);
    }


	/**
	* @param  \Illuminate\Http\Request  $request
	* @return \Illuminate\Http\Response
	*/
    public function distanceFeed(Request $request)
    {

        $data = $request->all();

        $distance = "";
        $time = "";
        $jobid = "";
        $session_time = "";
        $flagged = 'no';
        $manually_handled = 'no';
        $by_admin = 'no';
        $admincomment = "";

        $time_not_empty = false;
        $distance_not_empty = false;
        $admincomment_not_empty = false;
        $session_time_not_empty = false;
        $is_flagged = false;
        $is_manually_handled = false;
        $is_by_admin = false;

        $response = 'Record updated!';

        // It should be set because if is not set then why test if it is empty
        // If it is set then is the value provided empty or a bunch of empty spaces, that's why there's a trim involved and length of string

        if( isset($data['distance']) )
        {
        	if ( strlen(trim($data['distance'])) > 0 )
        	{
        		$distance = $data['distance'];

        		$distance_not_empty = true;	
        	}
        }

        if( isset($data['time']) )
        {
        	if ( strlen(trim($data['time'])) > 0 )
        	{
        		$time = $data['time'];

        		$time_not_empty = true;	
        	}
        }

        if( isset($data['job_id']) )
        {
        	if ( strlen(trim($data['job_id'])) > 0 )
        	{
        		$jobid = $data['jobid'];
        	}
        }

        if( isset($data['session_time']) )
        {
        	if ( strlen(trim($data['session_time'])) > 0 )
        	{
        		$session_time = $data['session_time'];

        		$session_time_not_empty = true;
        	}
        }


        if ($data['flagged'] == 'true') 
        {
	        if( isset($data['admincomment']) )
	        {
	        	if ( strlen(trim($data['admincomment'])) > 0 )
	        	{
	        		$flagged = 'yes';

	        		$is_flagged = true;

	        		$admincomment = $data['admincomment'];

	        		$admincomment_not_empty = true;
	        	}
	        	else
	        	{
	        		$response = 'Please, add comment';
	        	}
	        }
        }
        

        if ($data['manually_handled'] == 'true') 
        {
            $manually_handled = 'yes';

            $is_manually_handled = true;
        }


        if ($data['by_admin'] == 'true') 
        {
            $by_admin = 'yes';

            $is_by_admin = true;
        }



        if ( $time_not_empty || $distance_not_empty ) 
        {

            $affectedRows = Distance::where('job_id', '=', $jobid)->update(array('distance' => $distance, 'time' => $time));
        }


        if ( $admincomment_not_empty || $session_time_not_empty || $is_flagged || $is_manually_handled  || $is_by_admin ) {

            $affectedRows1 = Job::where('id', '=', $jobid)->update(array('admin_comments' => $admincomment, 'flagged' => $flagged, 'session_time' => $session, 'manually_handled' => $manually_handled, 'by_admin' => $by_admin));
        }

        return response($response);
    }

	/**
	* @param  \Illuminate\Http\Request  $request
	* @return \Illuminate\Http\Response
	*/
    public function reopen(Request $request)
    {
        $data = $request->all();

        $response = $this->repository->reopen($data);

        return response($response);
    }

	/**
	* @param  \Illuminate\Http\Request  $request
	* @return \Illuminate\Http\Response
	*/
    public function resendNotifications(Request $request)
    {
        $data = $request->all();

        $job = $this->repository->find($data['jobid']);

        $job_data = $this->repository->jobToData($job);

        $this->repository->sendNotificationTranslator($job, $job_data, '*');

        return response(['success' => 'Push sent']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {
        $data = $request->all();
        $job = $this->repository->find($data['jobid']);
        $job_data = $this->repository->jobToData($job);

        try {
            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);
        } catch (\Exception $e) {
            return response(['success' => $e->getMessage()]);
        }
    }

}	

?>