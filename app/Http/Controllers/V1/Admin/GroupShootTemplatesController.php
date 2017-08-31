<?php

namespace App\Http\Controllers\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\GroupShootTemplate;
use Illuminate\Http\Request;

class GroupShootTemplatesController extends Controller
{
    public function index()
    {
        $templates = GroupShootTemplate::orderByDesc('sort')->paginate();

        return view('admin.groupshoots.templates.index', compact('templates'));
    }

    /**
     *
     */
    public function create()
    {
        return view('admin.groupshoots.templates.create');
    }

    public function edit(GroupShootTemplate $template)
    {
        return view('admin.groupshoots.templates.edit', compact('template'));
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'title'              => 'required',
            'sort'               => 'required',
            'cover_url'          => 'required',
            'icon_url'           => 'required',
            'icon_url_selected'  => 'required',
            'template_water_url' => 'required',
        ]);

        GroupShootTemplate::create($request->all());

        return redirect('/admin/groupshoots/templates');
    }

    /**
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function destroy($id)
    {
        GroupShootTemplate::destroy($id);
        return redirect('/admin/groupshoots/templates');
    }

    public function update(GroupShootTemplate $template, Request $request)
    {
        $template->update($request->intersect(['sort', 'title', 'cover_url', 'icon_url', 'icon_url_selected','template_water_url']));

        return redirect('/admin/groupshoots/templates');
    }


    /**
     * @param GroupShootTemplate $template
     * @param Request            $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function updateSort(GroupShootTemplate $template, Request $request)
    {
        $actions = ['moveUp', 'moveDown'];

        $action = $request->input('action');
        if (!in_array($action, $actions)) {
            return redirect()->back();
        }

        $template->$action();
        return redirect('admin/groupshoots/templates');
    }

}
