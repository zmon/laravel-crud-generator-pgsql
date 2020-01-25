<?php


namespace App\Observers;

use App\[[model_uc]];

class [[model_uc]]Observer
{
    protected $request;

    public function __construct()
    {
        $this->request = request();
    }

    /**
     * Handle the charge "created" event.
     *
     * @param  [[model_uc]]  $model
     * @return void
     */
    public function created([[model_uc]] $model)
    {
        $model->saveHistory($this->request, __FUNCTION__);
    }

    /**
     * Handle the charge "updated" event.
     *
     * @param  [[model_uc]]  $model
     * @return void
     */
    public function updated([[model_uc]] $model)
    {
        $model->saveHistory($this->request, __FUNCTION__);
    }

    /**
     * Handle the charge "deleted" event.
     *
     * @param  [[model_uc]]  $model
     * @return void
     */
    public function deleting([[model_uc]] $model)
    {

//        $user = \Auth::User();
//        if ( $user ) {
//            $model->purged_by = $user->id;
//        } else {
//            $model->purged_by = -1;
//        }
//        $model->save();

    }

    /**
     * Handle the charge "deleted" event.
     *
     * @param  [[model_uc]]  $model
     * @return void
     */
    public function deleted([[model_uc]] $model)
    {
        $model->saveHistory($this->request, __FUNCTION__);
    }

    /**
     * Handle the charge "restored" event.
     *
     * @param  [[model_uc]]  $model
     * @return void
     */
    public function restored([[model_uc]] $model)
    {
        //
    }

    /**
     * Handle the charge "force deleted" event.
     *
     * @param  [[model_uc]]  $model
     * @return void
     */
    public function forceDeleted([[model_uc]] $model)
    {
        //
    }
}
