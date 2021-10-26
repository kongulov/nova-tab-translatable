<?php

namespace Kongulov\NovaTabTranslatable\Http\Controllers;

use Illuminate\Routing\Controller;
use Laravel\Nova\DeleteField;
use Laravel\Nova\Http\Requests\PivotFieldDestroyRequest;
use Laravel\Nova\Nova;

class PivotFieldDestroyController extends Controller
{
    /**
     * Delete the file at the given field.
     *
     * @param  \Laravel\Nova\Http\Requests\PivotFieldDestroyRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function handle(PivotFieldDestroyRequest $request)
    {
        abort(404);

        $request->authorizeForAttachment();

        DeleteField::forRequest(
            $request, $request->findFieldOrFail(),
            $pivot = $request->findPivotModel()
        )->save();

        Nova::actionEvent()->forAttachedResourceUpdate(
            $request, $request->findModelOrFail(), $pivot
        )->save();
    }
}
