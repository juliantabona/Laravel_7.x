<?php

namespace App\Traits;

use App\Http\Resources\Project as ProjectResource;
use App\Http\Resources\Projects as ProjectsResource;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

trait ProjectTraits
{
    public $project;
    public $project_to_clone = null;
    public $default_offline_offline_message = 'Sorry, we are currently offline';

    /*  convertToApiFormat() method:
     *
     *  Converts to the appropriate Api Response Format
     *
     */
    public function convertToApiFormat($projects = null)
    {
        if ($projects) {
            //  Transform the multiple instances
            return new ProjectsResource($projects);
        } else {
            //  Transform the single instance
            return new ProjectResource($this);
        }
    }

    /*  This method creates a new project
     */
    public function initiateCreate($request)
    {
        //  Validate the request
        $validation_data = $request->validate([
            'name' => 'required',
        ]);

        //  If we have the project id representing the project to clone
        if ($request->input('clone_project_id')) {
            //  Retrieve the project to clone
            $this->project_to_clone = \App\Project::where('id', $request->input('clone_project_id'))->with('versions')->first();
        }

        //  Set the template
        $template = [
            'online' => true,
            'active_version_id' => null,
            'user_id' => auth()->user()->id,
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'offline_message' => $this->default_offline_offline_message,
        ];

        try {
            /*
             *  Create new a project, then retrieve a fresh instance
             */
            $this->project = $this->create($template)->fresh();

            //  If created successfully
            if ($this->project) {
                $this->assignUserAsAdmin();

                // Create and assign a short code
                $this->createAndAssignShortCode($request);

                // Create and assign a version
                $this->createAndAssignVersion($request);

                //  Return a fresh instance
                return $this->project->fresh();
            }
        } catch (\Exception $e) {
            //  Throw a validation error
            throw ValidationException::withMessages(['general' => $e->getMessage()]);
        }
    }

    public function assignUserAsAdmin()
    {
        //  Associate the project with the current user as admin
        auth()->user()->projects()->save($this->project,
        //  Pivot table values
        [
            'type' => 'admin',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function createAndAssignShortCode($request)
    {
        //  Set the project id on the request
        $request->merge(['project_id' => $this->project->id]);

        //  Create a new shortcode
        $shortcode = ( new \App\ShortCode() )->initiateCreate($request);
    }

    public function createAndAssignVersion($request)
    {
        //  Set the project id on the request
        $request->merge(['project_id' => $this->project->id]);

        $active_version_id = null;

        //  If we have version to clone
        if ($this->project_to_clone) {
            //  Foreach version
            foreach ($this->project_to_clone->versions as $version_to_clone) {
                //  Retrieve the version details
                $request->merge(['user_id' => auth()->user()->id]);
                $request->merge(['number' => $version_to_clone->number]);
                $request->merge(['builder' => $version_to_clone->builder]);
                $request->merge(['description' => $version_to_clone->description]);

                //  Clone the version
                $version = ( new \App\Version() )->initiateCreate($request);

                //  If the version was cloned
                if ($version) {
                    //  Set the active version
                    if ($this->project_to_clone->active_version_id == $version_to_clone->id) {
                        $this->project->update([
                            'active_version_id' => $version->id,
                        ]);
                    }
                }
            }
        } else {
            //  Create a new version
            $version = ( new \App\Version() )->initiateCreate($request);

            //  If the version was cloned
            if ($version) {
                $this->project->update([
                    'active_version_id' => $version->id,
                ]);
            }
        }
    }

    public function getLiveAnalytics()
    {
        return $this->getAnalytics(false);
    }

    public function getTestAnalytics()
    {
        return $this->getAnalytics(true);
    }

    public function getAnalytics($test = null)
    {
        if ($test === false) {
            //  Target only live sessions
            $sessions = $this->liveSessions()->orderBy('created_at');
        } elseif ($test === true) {
            //  Target only test sessions
            $sessions = $this->testSessions()->orderBy('created_at');
        } else {
            //  Target all sessions
            $sessions = $this->sessions()->orderBy('created_at');
        }

        //  Get the sessions
        $sessions = collect($sessions->get());

        //  Get the unique sessions
        $unique_sessions = collect($sessions)->unique('msisdn');

        //  Get the sessions by status
        $sessions_by_status = collect($sessions)->groupBy(function ($session, $key) {
            return $session['status']['name'];
        });

        $unique_sessions_by_status = collect($unique_sessions)->groupBy(function ($session, $key) {
            return $session['status']['name'];
        });

        $supported_statuses = ['Active', 'Closed', 'Timeout', 'Fail'];

        /***************************************
         *  GENERAL METRICS                    *
         **************************************/

        //  Calculate the total number of sessions
        $total_sessions = collect($sessions)->count();

        //  Calculate the total number of unique sessions
        $total_unique_sessions = collect($unique_sessions)->count();

        //  Calculate the total number of sessions by status
        $total_sessions_by_status = $sessions_by_status->map(function ($sessions_grouped_by_status, $key) {
            $total_sessions = collect($sessions_grouped_by_status)->count();

            return $total_sessions;
        })->toArray();

        //  Calculate the total number of unique sessions by status
        $total_unique_sessions_by_status = $unique_sessions_by_status->map(function ($sessions_grouped_by_status, $key) {
            $total_unique_sessions = collect($sessions_grouped_by_status)->count();

            return $total_unique_sessions;
        })->toArray();

        //  Set Non-Existent statuses equal to Zero
        foreach ($supported_statuses as $status) {
            if (!isset($total_sessions_by_status[$status])) {
                $total_sessions_by_status[$status] = 0;
            }
            if (!isset($total_unique_sessions_by_status[$status])) {
                $total_unique_sessions_by_status[$status] = 0;
            }
        }

        //  Sort the sessions by status alphabetically
        ksort($total_sessions_by_status);
        ksort($total_unique_sessions_by_status);

        //  Calculate the Fail rate
        $fail_rate = isset($total_sessions_by_status['Fail']) && $total_sessions != 0 ? round($total_sessions_by_status['Fail'] / $total_sessions * 100, 1) : 0;

        //  Calculate the Timeout rate
        $timeout_rate = isset($total_sessions_by_status['Timeout']) && $total_sessions != 0 ? round($total_sessions_by_status['Timeout'] / $total_sessions * 100, 1) : 0;

        //  Calculate the Closed rate
        $fulfillment_rate = isset($total_sessions_by_status['Closed']) && $total_sessions != 0 ? round($total_sessions_by_status['Closed'] / $total_sessions * 100, 1) : 0;

        /*****************************************
         *  METRICS FOR CREATING VISUAL GRAPHS   *
         ****************************************/

        //  Get sessions over time
        $sessions_over_time = $sessions->groupBy(function ($session, $key) {
            return $this->getGroupingDateFormat($session);
        });

        //  Get sessions over status
        $sessions_over_status = $sessions->groupBy(function ($session, $key) {
            return $session['status']['name'];
        });

        //  Set Non-Existent statuses equal to an empty array
        foreach ($supported_statuses as $status) {
            if (!isset($sessions_over_status[$status])) {
                $sessions_over_status[$status] = [];
            }
        }

        //  Sort the sessions by status alphabetically
        $sessions_over_status = collect($sessions_over_status)->toArray();
        ksort($sessions_over_status);

        //  Get the dates between the start date and end date
        $dates_between = $this->getAnalyticsDatesBetweenStartAndEndDatetime();

        //  Generate dates with default data set to zero values
        $zero_sessions_over_time = collect($dates_between)->map(function ($date_between, $key) {
            return [$date_between => [
                'total_sessions' => 0,
                'total_unique_sessions' => 0,
            ]];
        })->collapse();

        //  Generate dates with default data set to zero values
        $zero_session_status_rate_over_time = collect($dates_between)->map(function ($date_between, $key) {
            return [$date_between => [
                'rate' => 0,
            ]];
        })->collapse();

        /*********************************************
         *  Calculate the total sessions over time   *
         *********************************************/
        $total_sessions_over_time = $sessions_over_time->map(function ($sessions, $key) {
            $total_sessions = collect($sessions)->count();
            $total_unique_sessions = collect($sessions)->unique('msisdn')->count();

            return [
                'total_sessions' => $total_sessions,
                'total_unique_sessions' => $total_unique_sessions,
            ];
        });

        $total_sessions_over_time = $zero_sessions_over_time->merge($total_sessions_over_time);

        /**************************************************
         *  Calculate the total sessions rate over time   *
         *************************************************/
        $sessions_rate_over_time = $total_sessions_over_time->map(function ($group_session_totals, $key) use ($total_sessions, $total_unique_sessions) {
            $group_total_sessions = $group_session_totals['total_sessions'];
            $group_total_unique_sessions = $group_session_totals['total_unique_sessions'];

            //  Calculate the Total Sessions rate
            $sessions_rate = ($group_total_sessions != 0 && $total_sessions != 0) ? round($group_total_sessions / $total_sessions * 100, 1) : 0;

            //  Calculate the Total Unique Sessions rate
            $unique_sessions_rate = ($group_total_unique_sessions != 0 && $total_sessions != 0) ? round($group_total_unique_sessions / $total_sessions * 100, 1) : 0;

            return [
                'sessions_rate' => $sessions_rate,
                'unique_sessions_rate' => $unique_sessions_rate,
            ];
        });

        /*******************************************************
         *  Calculate the total sessions by status over time   *
         ******************************************************/
        $total_sessions_by_status_over_time = collect($sessions_over_status)->map(function ($sessions_grouped_by_status, $key) use ($zero_sessions_over_time) {
            $total_sessions_over_time = collect($sessions_grouped_by_status)->groupBy(function ($session, $key) {
                return $this->getGroupingDateFormat($session);
            })->map(function ($sessions, $key) {
                $total_sessions = collect($sessions)->count();
                $total_unique_sessions = collect($sessions)->unique('msisdn')->count();

                return [
                    'total_sessions' => $total_sessions,
                    'total_unique_sessions' => $total_unique_sessions,
                ];
            });

            $total_sessions_over_time = $zero_sessions_over_time->merge($total_sessions_over_time);

            return $total_sessions_over_time;
        });

        //  Calculate the fail rate over time
        $session_status_rate_over_time = collect($sessions_over_status)->map(function ($sessions_grouped_by_status, $key) use ($zero_session_status_rate_over_time, $total_sessions) {
            $session_status_rate_over_time = collect($sessions_grouped_by_status)->groupBy(function ($session, $key) {
                return $this->getGroupingDateFormat($session);
            })->map(function ($sessions) use ($total_sessions, $key) {
                //  Count the Status sessions
                $total_status_sessions = collect($sessions)->count();

                //  Calculate the Status rate
                $rate = ($total_status_sessions != 0 && $total_sessions != 0) ? round($total_status_sessions / $total_sessions * 100, 1) : 0;

                return [
                    'rate' => $rate,
                ];
            });

            $session_status_rate_over_time = $zero_session_status_rate_over_time->merge($session_status_rate_over_time);

            return $session_status_rate_over_time;
        });

        /*******************************************************
         *  Calculate the total sessions by hours of the day   *
         *******************************************************/
        $sessions_by_hours_of_day = $sessions->groupBy(function ($session, $key) {
            return Carbon::parse($session['created_at'])->format('H:00');
        })->map(function ($sessions) {
            //  Count the sessions grouped by the Hour e.g '01:00', '02:00', ... '23:00'
            $total_sessions = collect($sessions)->count();

            return $total_sessions;
        })->toArray();

        $total_sessions_by_hours_of_day = [];
        $hours_of_day = ['00:00', '01:00', '02:00', '03:00', '04:00', '05:00', '06:00', '07:00', '08:00', '09:00', '10:00', '11:00',
                         '12:00', '13:00', '14:00', '15:00', '16:00', '17:00', '18:00', '19:00', '20:00', '21:00', '22:00', '23:00'];

        //  Set missing hours to Zero
        for ($x = 0; $x < count($hours_of_day); ++$x) {

            $hour = $hours_of_day[$x];

            if (isset($sessions_by_hours_of_day[$hour])) {
                $total_sessions_by_hours_of_day[$hour] = $sessions_by_hours_of_day[$hour];
            }else{
                $total_sessions_by_hours_of_day[$hour] = 0;
            }
        }

        /*******************************************************
         *  Calculate the total sessions by days of the week   *
         *******************************************************/
        $sessions_by_days_of_week = $sessions->groupBy(function ($session, $key) {
            return Carbon::parse($session['created_at'])->format('l');
        })->map(function ($sessions) {
            //  Count the sessions grouped by the Days of the week e.g 'Monday', 'Tuesday', ... 'Sunday'
            $total_sessions = collect($sessions)->count();

            return $total_sessions;
        })->toArray();

        $total_sessions_by_days_of_week = [];
        $days_of_week = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        //  Set missing days to Zero
        for ($x = 0; $x < count($days_of_week); ++$x) {
            $day = $days_of_week[$x];

            if (isset($sessions_by_days_of_week[$day])) {
                $total_sessions_by_days_of_week[$day] = $sessions_by_days_of_week[$day];
            }else{
                $total_sessions_by_days_of_week[$day] = 0;
            }
        }

        /*******************************************************
         *  Calculate the total sessions by days of the month   *
         *******************************************************/
        $sessions_by_days_of_month = $sessions->groupBy(function ($session, $key) {
            return Carbon::parse($session['created_at'])->format('d');
        })->map(function ($sessions) {
            //  Count the sessions grouped by the Days of the month e.g '01', '02', ... '31'
            $total_sessions = collect($sessions)->count();

            return $total_sessions;
        })->toArray();

        $total_sessions_by_days_of_month = [];
        $days_of_month = ['01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16',
                          '17', '18', '19', '20', '21', '22', '23', '24', '25', '26', '27', '28', '29', '30', '31'];

        //  Set missing days to Zero
        for ($x = 0; $x < count($days_of_month); ++$x) {

            $day = $days_of_month[$x];

            if (isset($sessions_by_days_of_month[$day])) {
                $total_sessions_by_days_of_month[$day] = $sessions_by_days_of_month[$day];
            }else{
                $total_sessions_by_days_of_month[$day] = 0;
            }
        }

        /*********************************************************
         *  Calculate the total sessions by months of the year   *
         ********************************************************/
        $sessions_by_months_of_year = $sessions->groupBy(function ($session, $key) {
            return Carbon::parse($session['created_at'])->format('F');
        })->map(function ($sessions) {
            //  Count the sessions grouped by the Days of the month e.g 'January', 'February', ... 'December'
            $total_sessions = collect($sessions)->count();

            return $total_sessions;
        })->toArray();

        $total_sessions_by_months_of_year = [];
        $months_of_year = ['January', 'February', 'March', 'April', 'May', 'June', 'July',
                           'August', 'September', 'October', 'November', 'December'];

        //  Set missing months to Zero
        for ($x = 0; $x < count($months_of_year); ++$x) {
            $month = $months_of_year[$x];

            if (isset($sessions_by_months_of_year[$month])) {
                $total_sessions_by_months_of_year[$month] = $sessions_by_months_of_year[$month];
            }else{
                $total_sessions_by_months_of_year[$month] = 0;
            }
        }

        /*
        $total_sessions_over_time = $sessions_over_time->map(function ($sessions, $key) {
            $total_sessions = collect($sessions)->count();
            $total_unique_sessions = collect($sessions)->unique('msisdn')->count();


            return Carbon::createFromFormat('Y-m-d H:i:s', Carbon::parse($session['created_at']))->format('Y-m-d H');

            return [
                'total_sessions' => $total_sessions,
                'total_unique_sessions' => $total_unique_sessions,
            ];
        });

        $total_sessions_over_time = $zero_sessions_over_time->merge($total_sessions_over_time);
        */

        return [
            /*

            //  GENERAL METRICS
            'total_sessions' => $total_sessions,
            'total_unique_sessions' => $total_unique_sessions,
            'total_sessions_by_status' => $total_sessions_by_status,
            'total_unique_sessions_by_status' => $total_unique_sessions_by_status,
            'fail_rate' => $fail_rate,
            'timeout_rate' => $timeout_rate,
            'fulfillment_rate' => $fulfillment_rate,

            */
            //  GENERAL METRICS
            'general' => [
                'total_sessions' => [
                    'name' => 'Total Sessions',
                    'value' => $total_sessions,
                ],
                'total_unique_sessions' => [
                    'name' => 'Total Unique Sessions',
                    'value' => $total_unique_sessions,
                ],
                'total_sessions_by_status' => [
                    'name' => 'Total Sessions By Status',
                    'statuses' => $total_sessions_by_status,
                ],
                'total_unique_sessions_by_status' => [
                    'name' => 'Total Sessions By Status',
                    'statuses' => $total_unique_sessions_by_status,
                ],
            ],

            //  METRICS FOR CREATING VISUAL GRAPHS
            'total_sessions_over_time' => $total_sessions_over_time,
            'total_sessions_by_status_over_time' => $total_sessions_by_status_over_time,
            'session_status_rate_over_time' => $session_status_rate_over_time,
            'sessions_rate_over_time' => $sessions_rate_over_time,

            'total_sessions_by_hours_of_day' => $total_sessions_by_hours_of_day,
            'total_sessions_by_days_of_week' => $total_sessions_by_days_of_week,
            'total_sessions_by_days_of_month' => $total_sessions_by_days_of_month,
            'total_sessions_by_months_of_year' => $total_sessions_by_months_of_year,
        ];
    }

    public function getGroupingDateFormat($session)
    {
        //  Get the start date provided by the request payload otherwise use last months datetime
        $interval = request()->input('interval') ?? (Carbon::now())->subMonth()->format('Y-m-d H:i:s');

        if ($interval == 'hours') {
            //  Group by hrs
            return Carbon::createFromFormat('Y-m-d H:i:s', Carbon::parse($session['created_at']))->format('Y-m-d H');
        } elseif ($interval == 'days') {
            //  Group by days
            return Carbon::createFromFormat('Y-m-d H:i:s', Carbon::parse($session['created_at']))->format('Y-m-d');
        } elseif ($interval == 'months') {
            //  Group by months
            return Carbon::createFromFormat('Y-m-d H:i:s', Carbon::parse($session['created_at']))->format('Y-m');
        } elseif ($interval == 'years') {
            //  Group by months
            return Carbon::createFromFormat('Y-m-d H:i:s', Carbon::parse($session['created_at']))->format('Y');
        } else {
            //  Group by days
            return Carbon::createFromFormat('Y-m-d H:i:s', Carbon::parse($session['created_at']))->format('Y-m-d');
        }
    }

    public function getAnalyticsStartDate()
    {
        //  Get the start date provided by the request payload otherwise use last months datetime
        $start_date = request()->input('start_date') ?? (Carbon::now())->subMonth()->format('Y-m-d 00:00:00');

        return $start_date;
    }

    public function getAnalyticsEndDate()
    {
        //  Get the end date provided by the request payload otherwise use todays datetime but subtract one month
        $end_time = request()->input('end_date') ?? (Carbon::now())->format('Y-m-d 00:00:00');

        return new Carbon($end_time);
    }

    public function getAnalyticsDatesBetweenStartAndEndDatetime()
    {
        //  Get the interval
        $interval = request()->input('interval') ?? (Carbon::now())->subMonth()->format('Y-m-d H:i:s');

        //  Get the dates between the query start and end datetime
        $dates_between = \Carbon\CarbonPeriod::create($this->getAnalyticsStartDate(), $this->getAnalyticsEndDate());

        $dates_between_formatted = [];

        //  Iterate over the dates between
        foreach ($dates_between as $key => $date) {
            $dates_between_formatted[$key] = $date->format('Y-m-d H');

            if ($interval == 'hours') {
                //  Start 1 day before
                $dates_between_formatted[$key] = $date->format('Y-m-d 00');
            } elseif ($interval == 'days') {
                //  Start 1 month before
                $dates_between_formatted[$key] = $date->format('Y-m-d');
            } elseif ($interval == 'months') {
                //  Start 1 year before
                $dates_between_formatted[$key] = $date->format('Y-m');
            } elseif ($interval == 'years') {
                //  Start 5 years before
                $dates_between_formatted[$key] = $date->format('Y');
            } else {
                //  Start 1 month before
                $dates_between_formatted[$key] = $date->format('Y-m-d');
            }
        }

        // Convert the dates between to an array of dates
        return $dates_between_formatted;
    }

    /*
     *  Checks if a given user is the owner of the project
     */
    public function isOwner($user_id)
    {
        return $this->whereUserId($user_id)->exists();
    }

    /*
     *  Checks if a given user is the admin of the project
     *
     *  Technical administrators have complete control over the project. They can appoint new users
     *  and change existing user roles, except Owner roles. They create, edit and delete existing
     *  projects. They can view, and manage "Billing" and "Subscription" details of the project
     *  actions. Basically they manage everything.
     */
    public function isAdmin($user_id = null)
    {
        if ($user_id) {
            return $this->users()->wherePivot('user_id', $user_id)->wherePivot('type', 'admin')->exists();
        }
    }

    /*
     *  Checks if a given user is the editor of the project
     *
     *  Technical editors have control to edit the project. They cannot appoint new users
     *  or change existing user roles. They also cannot delete the project or view any of
     *  the project "Billing" and "Subscription" details. Basically they only build and
     *  manage the technical side of the project.
     */
    public function isEditor($user_id = null)
    {
        if ($user_id) {
            return $this->users()->wherePivot('user_id', $user_id)->wherePivot('type', 'editor')->exists();
        }
    }

    /*
     *  Checks if a given user is the viewer of the project
     *
     *  Ideal for finance and accounting staff, this role gives complete access to "Billing"
     *  and "Subscription" details of the project as well as the ability to make necessary
     *  actions. Basically they only manage the business side of the project.
     */
    public function isBiller($user_id = null)
    {
        if ($user_id) {
            return $this->users()->wherePivot('user_id', $user_id)->wherePivot('type', 'biller')->exists();
        }
    }

    /*
     *  Checks if a given user is the viewer of the project
     *
     *  Viewers can only view "Billing" and "Subscription" details of the project.
     *  They cannot edit anything at all. Basically they only oversee the business
     *  side of the project.
     */
    public function isViewer($user_id = null)
    {
        if ($user_id) {
            return $this->users()->wherePivot('user_id', $user_id)->wherePivot('type', 'viewer')->exists();
        }
    }
}
