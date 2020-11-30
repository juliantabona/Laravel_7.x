<template>

    <Row :gutter="12">
        
        <Col :span="20" :offset="2">

            <Row :gutter="12" class="border-bottom-dashed mb-3">

                <Col :span="8" class="clearfix">
            
                    <Button type="default" size="large" class="mt-3 mb-3" @click.native="navigateToProject()">
                        <Icon type="md-arrow-back" class="mr-1" :size="20" />
                        <span>Back</span>
                    </Button>
                
                </Col>

                <Col :span="8">

                    <h1 class="text-center py-3 mb-3">Analytics</h1>

                </Col>

                <Col :span="8" class="clearfix">

                    <div class="float-right mb-3 py-3">
                            
                        <span class="d-inline-block mr-2">Type:</span>

                        <Select v-model="analyticsUrl" style="width:200px" @on-change="fetchAnalytics()">
                            <Option v-for="endpoint in analyticEndpoints" :value="endpoint.url" :key="endpoint.name">
                                {{ endpoint.name }}
                            </Option>
                        </Select>

                    </div>

                </Col>

            </Row>

            <Row :gutter="12">

                <Col :span="24">

                    <div class="clearfix">
                
                        <Button type="default" size="large" class="mt-3 mb-3 float-right"
                                @click.native="fetchAnalytics()" :loading="isLoading" 
                                :disabled="isLoading">
                            <Icon v-show="!isLoading" type="ios-refresh" class="mr-1" :size="20" />
                            <span>Refresh</span>
                        </Button>
                    
                    </div>

                    <Divider orientation="left">Total Sessions</Divider>

                    <Row v-if="analytics" :gutter="12">

                        <Col :span="6" v-for="(metric, index) in Object.keys(analytics.general)" :key="index" class="mb-3">

                            <Card v-if="['total_sessions', 'total_unique_sessions'].includes(metric)" 
                                  :class="['py-2', activeTotalSessions == metric ? 'bg-primary text-white' : '']"
                                  @click.native="activeTotalSessions = metric">
                                
                                <h6 :class="['text-center my-2', activeTotalSessions == metric ? 'text-white' : 'text-muted']">{{ analytics.general[metric]['name'] }}</h6>

                                <h1 :style="{ fontSize: '40px' }" class="font-weight-bold text-center">{{ analytics.general[metric]['value'] }}</h1>

                            </Card>

                        </Col>

                    </Row>

                    <div v-if="showTotalSessionsByStatus">

                        <Divider orientation="left">Total Sessions By Status</Divider>
                        
                        <Row v-if="analytics" :gutter="12">
                            
                            <Col :span="6" v-for="(status_name, index) in Object.keys(analytics.general['total_sessions_by_status']['statuses'])" :key="index" class="mb-3">

                                <Card class="py-2">
                                    
                                    <h6 :class="['text-center', 'my-2', 'font-weight-bold']" :style="{ color: getStatusColor(status_name) }">{{ status_name }}</h6>

                                    <h1 :class="['font-weight-bold', 'text-center']" :style="{ fontSize: '40px', color: getStatusColor(status_name) }">
                                        {{ analytics.general['total_sessions_by_status']['statuses'][status_name] }}
                                    </h1>

                                </Card>

                            </Col>

                        </Row>

                    </div>

                    <div v-else-if="showTotalUniqueSessionsByStatus">
                        
                        <Divider orientation="left">Total Unique Sessions By Status</Divider>
                        
                        <Row v-if="analytics" :gutter="12">
                            
                            <Col :span="6" v-for="(status_name, index) in Object.keys(analytics.general['total_unique_sessions_by_status']['statuses'])" :key="index" class="mb-3">

                                <Card class="py-2">
                                    
                                    <h6 :class="['text-center', 'my-2', 'font-weight-bold']" :style="{ color: getStatusColor(status_name) }">{{ status_name }}</h6>

                                    <h1 :class="['font-weight-bold', 'text-center']" :style="{ fontSize: '40px', color: getStatusColor(status_name) }">
                                        {{ analytics.general['total_unique_sessions_by_status']['statuses'][status_name] }}
                                    </h1>

                                </Card>

                            </Col>

                        </Row>

                    </div>

                    <Row :gutter="12" class="my-4">

                        <Col :span="12" v-for="(chart, index) in charts" :key="index" class="mb-3">
                            
                            <Card>

                                <h4 class="font-weight-bold text-dark">{{ chart.title }}</h4>
                                
                                <Divider class="mt-2 mb-2"></Divider>

                                <span :style="{ fontSize: '12px', textAlign: 'justify' }" class="d-block">
                                    {{ chart.desc }}
                                </span>

                                <Divider class="mt-2 mb-2"></Divider>

                                <lineChart v-if="chart.chartType == 'Line'" :chartdata="chart.chartData" :options="chart.chartOptions || defaultChartOptions" :style="chartStyles"/>
                                
                                <barChart v-if="chart.chartType == 'Bar'" :chartdata="chart.chartData" :options="chart.chartOptions || defaultChartOptions" :style="chartStyles"/>

                            </Card>

                        </Col>

                    </Row>

                </Col>

            </Row>

        </Col>

    </Row>

</template>

<script>

    import lineChart from './../../../../../components/_common/charts/lineChart.vue';
    import barChart from './../../../../../components/_common/charts/barChart.vue';
    import moment from 'moment';

    export default {
        components: { lineChart, barChart },
        props: {
            project: {
                type: Object,
                default: null
            }
        },
        data(){
            return {
                isLoading: false,
                analyticsUrl: null,
                analytics: null,
                charts: [],
                activeTotalSessions: 'total_sessions',
                defaultChartOptions: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        yAxes: [{
                            ticks: {
                                stepSize: 5,
                                beginAtZero: true
                            }
                        }],
                        xAxes: [{
                            ticks: {
                                display: true,      //  this will remove only the x-axis label (at the bottom)
                                beginAtZero: true
                            }
                        }]
                    }
                },
                chartStyles: {
                    height: '250px'
                }
            }
        },
        computed: {
            analyticEndpoints(){
                return [
                    { 
                        name: 'All Analytics', 
                        url: this.project['_links']['sce:analytics'].href
                    },
                    { 
                        name: 'Live Analytics', 
                        url: this.project['_links']['sce:live_analytics'].href
                    },
                    { 
                        name: 'Test Analytics', 
                        url: this.project['_links']['sce:test_analytics'].href
                    }
                ];
            },
            showTotalSessionsByStatus(){
                return (this.activeTotalSessions == 'total_sessions');
            },
            showTotalUniqueSessionsByStatus(){
                return (this.activeTotalSessions == 'total_unique_sessions');
            }
        },
        methods: {
            navigateToProject(){
                /** Note that using router.push() or router.replace() does not allow us to make a
                 *  page refresh when visiting routes. This is undesirable at this moment since our 
                 *  parent component contains the <router-view />. When the page does not refresh, 
                 *  the <router-view /> is not able to receice the nested components defined in the 
                 *  route.js file. This means that we are then not able to render the nested 
                 *  components and present them. To counter this issue we must construct the 
                 *  href and use "window.location.href" to make a hard page refresh.
                 */
                var projectUrl = this.project['_links']['self'].href;
                //  Add the "menu" query to our current project route
                var route = { name: 'show-project-builder', params: { project_url: encodeURIComponent(projectUrl) } };
                //  Contruct the full path url
                var href = window.location.origin + "/" + VueInstance.$router.resolve(route).href
                //  Visit the url
                window.location.href = href;
            },
            moment: function () {
                return moment();
            },
            getStatusColor(status_name){
                if( status_name == 'Active'){
                    return '#2d8cf0';
                }else if( status_name == 'Closed'){
                    return '#19be6b';
                }else if( status_name == 'Timeout'){
                    return '#ff9900';
                }else if( status_name == 'Fail'){
                    return '#ed4014';
                }
            },
            getCharts() {

                if( this.analytics ){

                    var total_sessions_over_time = ((this.analytics || {}).total_sessions_over_time || {});
                    var sessions_rate_over_time = ((this.analytics || {}).sessions_rate_over_time || {});

                    var total_sessions_by_hours_of_day = ((this.analytics || {}).total_sessions_by_hours_of_day || {});
                    var total_sessions_by_days_of_week = ((this.analytics || {}).total_sessions_by_days_of_week || {});
                    var total_sessions_by_days_of_month = ((this.analytics || {}).total_sessions_by_days_of_month || {});
                    var total_sessions_by_months_of_year = ((this.analytics || {}).total_sessions_by_months_of_year || {});

                    return [
                        {
                            title: 'Sessions Over Time',
                            desc: 'This measures total number of sessions over time. By studying the number of sessions over time we are able to learn the adoption rate ',
                            chartType: 'Line',
                            chartData: {
                                labels: Object.keys(total_sessions_over_time).map(function(key) {
                                            return key
                                        }),
                                datasets: [
                                    {
                                        fill: false,
                                        lineTension: 0,
                                        borderColor: '#145DA0',
                                        label: 'Sessions Over Time',
                                        data: Object.keys(total_sessions_over_time).map(function(key) {
                                            return total_sessions_over_time[key]['total_sessions']
                                        })
                                    },
                                    {
                                        fill: false,
                                        lineTension: 0,
                                        borderColor: '#008b00',
                                        label: 'Total Unique Sessions Over Time',
                                        data: Object.keys(total_sessions_over_time).map(function(key) {
                                            return total_sessions_over_time[key]['total_unique_sessions']
                                        })
                                    }
                                ]
                            }
                        },
                        {
                            title: 'Sessions Rate Over Time',
                            desc: 'This measures total number of sessions over time. By studying the number of sessions over time we are able to learn the adoption rate ',
                            chartType: 'Line',
                            chartData: {
                                labels: Object.keys(total_sessions_over_time).map(function(key) {
                                            return key
                                        }),
                                datasets: [
                                    {
                                        fill: false,
                                        lineTension: 0,
                                        borderColor: '#145DA0',
                                        label: 'Sessions Rate Over Time',
                                        data: Object.keys(sessions_rate_over_time).map(function(key) {
                                            return sessions_rate_over_time[key]['sessions_rate']
                                        })
                                    },
                                    {
                                        fill: false,
                                        lineTension: 0,
                                        borderColor: '#008b00',
                                        label: 'Unique Sessions Rate Over Time',
                                        data: Object.keys(sessions_rate_over_time).map(function(key) {
                                            return sessions_rate_over_time[key]['unique_sessions_rate']
                                        })
                                    }
                                ]
                            },
                            chartOptions: Object.assign({}, this.defaultChartOptions, {
                                scales: {
                                    yAxes: [{
                                        ticks: {
                                            stepSize: 5,
                                            // Include a dollar sign in the ticks
                                            callback: function(value, index, values) {
                                                return value + '%';
                                            }
                                        },
                                        stepSize: 1,
                                        beginAtZero: true,
                                    }],
                                    xAxes: [{
                                        ticks: {
                                            display: false      //  this will remove only the x-axis label (at the bottom)
                                        }
                                    }]
                                }
                            })
                        },
                        {
                            title: 'Sessions By Status Over Time',
                            desc: 'This measures total number of sessions over time.',
                            chartType: 'Line',
                            chartData: {
                                labels: Object.keys(total_sessions_over_time).map(function(key) {
                                            return key
                                        }),
                                datasets: this.getTotalSessionsByStatusOverTime()
                            }
                        },
                        {
                            title: 'Sessions Status Rate Over Time',
                            desc: 'This measures total number of sessions over time.',
                            chartType: 'Line',
                            chartData: {
                                labels: Object.keys(total_sessions_over_time).map(function(key) {
                                            return key
                                        }),
                                datasets: this.getTotalSessionsStatusRateOverTime()
                            },
                            chartOptions: Object.assign({}, this.defaultChartOptions, {
                                scales: {
                                    yAxes: [{
                                        ticks: {
                                            stepSize: 5,
                                            // Include a dollar sign in the ticks
                                            callback: function(value, index, values) {
                                                return value + '%';
                                            }
                                        },
                                        stepSize: 1,
                                        beginAtZero: true,
                                    }],
                                    xAxes: [{
                                        ticks: {
                                            display: false      //  this will remove only the x-axis label (at the bottom)
                                        }
                                    }]
                                }
                            })
                        },
                        
                        {
                            title: 'Sessions By Hours Of Day',
                            desc: 'This measures total number of sessions over the 24 hours of the day',
                            chartType: 'Bar',
                            chartData: {
                                labels: Object.keys(total_sessions_by_hours_of_day).map(function(key) {
                                            return key
                                        }),
                                datasets: [
                                    {
                                        backgroundColor: '#145DA0',
                                        label: 'Sessions By Hours Of Day',
                                        data: Object.keys(total_sessions_by_hours_of_day).map(function(key) {
                                            return total_sessions_by_hours_of_day[key]
                                        })
                                    }
                                ]
                            }
                        },
                        {
                            title: 'Sessions By Days Of Week',
                            desc: 'This measures total number of sessions over the days of the week',
                            chartType: 'Bar',
                            chartData: {
                                labels: Object.keys(total_sessions_by_days_of_week).map(function(key) {
                                            return key
                                        }),
                                datasets: [
                                    {
                                        backgroundColor: '#145DA0',
                                        label: 'Sessions By Days Of Week',
                                        data: Object.keys(total_sessions_by_days_of_week).map(function(key) {
                                            return total_sessions_by_days_of_week[key]
                                        })
                                    }
                                ]
                            }
                        },
                        {
                            title: 'Sessions By Days Of Month',
                            desc: 'This measures total number of sessions over the days of the month',
                            chartType: 'Bar',
                            chartData: {
                                labels: Object.keys(total_sessions_by_days_of_month).map(function(key) {
                                            return key
                                        }),
                                datasets: [
                                    {
                                        backgroundColor: '#145DA0',
                                        label: 'Sessions By Days Of Month',
                                        data: Object.keys(total_sessions_by_days_of_month).map(function(key) {
                                            return total_sessions_by_days_of_month[key]
                                        })
                                    }
                                ]
                            }
                        },
                        {
                            title: 'Sessions By Month Of Year',
                            desc: 'This measures total number of sessions over the months of the year',
                            chartType: 'Bar',
                            chartData: {
                                labels: Object.keys(total_sessions_by_months_of_year).map(function(key) {
                                            return key
                                        }),
                                datasets: [
                                    {
                                        backgroundColor: '#145DA0',
                                        label: 'Sessions By Days Of Month',
                                        data: Object.keys(total_sessions_by_months_of_year).map(function(key) {
                                            return total_sessions_by_months_of_year[key]
                                        })
                                    }
                                ]
                            }
                        },
                    ];

                }
            },
            getTotalSessionsByStatusOverTime(){

                var total_sessions_by_status_over_time = ((this.analytics || {}).total_sessions_by_status_over_time || {});

                var datasets = [];

                for (let x = 0; x < Object.keys(total_sessions_by_status_over_time).length; x++) {
                    
                    //  ['Active', 'Closed', 'Timeout', 'Fail']
                    var status_key = Object.keys(total_sessions_by_status_over_time)[x];

                    /** { Active: { 2020-10-28 13: [ ... ] }  } or
                     *  { Closed: { 2020-10-28 12: [ ... ] }  } or
                     *  { Timeout: { 2020-10-28 11: [ ... ] } } or
                     *  { Fail: { 2020-10-28 10: [ ... ] } }
                     */
                    var total_sessions_over_time = total_sessions_by_status_over_time[status_key];

                    var label = status_key;

                    if( status_key == 'Active'){
                        var borderColor = '#1CDB00';
                    }else if( status_key == 'Closed'){
                        var borderColor = '#008b00';
                    }else if( status_key == 'Timeout'){
                        var borderColor = '#FAA00E';
                    }else if( status_key == 'Fail'){
                        var borderColor = '#FA2816';
                    }

                    datasets.push({
                        label: label,       //  Active, Closed, Timeout, Fail
                        fill: false,        // Remove background color below the line
                        lineTension: 0,
                        borderColor: borderColor,
                        data: Object.keys(total_sessions_over_time).map(function(key) {
                            return total_sessions_over_time[key]['total_sessions']
                        }),
                    });
                    
                }

                return datasets;
            },
            getTotalSessionsStatusRateOverTime(){

                var session_status_rate_over_time = ((this.analytics || {}).session_status_rate_over_time || {});

                var datasets = [];

                for (let x = 0; x < Object.keys(session_status_rate_over_time).length; x++) {
                    
                    //  ['Active', 'Closed', 'Timeout', 'Fail']
                    var status_key = Object.keys(session_status_rate_over_time)[x];

                    /** { Active: { 2020-10-28 13: [ ... ] }  } or
                     *  { Closed: { 2020-10-28 12: [ ... ] }  } or
                     *  { Timeout: { 2020-10-28 11: [ ... ] } } or
                     *  { Fail: { 2020-10-28 10: [ ... ] } }
                     */
                    var total_sessions_over_time = session_status_rate_over_time[status_key];

                    var label = status_key;

                    if( status_key == 'Active'){
                        var borderColor = '#1CDB00';
                    }else if( status_key == 'Closed'){
                        var borderColor = '#008b00';
                    }else if( status_key == 'Timeout'){
                        var borderColor = '#FAA00E';
                    }else if( status_key == 'Fail'){
                        var borderColor = '#FA2816';
                    }

                    datasets.push({
                        label: label,           //  Active, Closed, Timeout, Fail
                        fill: false,            // Remove background color below the line
                        lineTension: 0,
                        borderColor: borderColor,
                        data: Object.keys(total_sessions_over_time).map(function(key) {
                            return total_sessions_over_time[key]['rate']
                        }),
                    });
                    
                }

                return datasets;
            },
            fetchAnalytics() {

                //  If we have the analytics url
                if( this.analyticsUrl ){

                    //  Hold constant reference to the current Vue instance
                    const self = this;

                    //  Start loader
                    self.isLoading = true;

                    console.log('Fetch analytics');

                    //  Use the api call() function, refer to api.js
                    api.call('get', this.analyticsUrl)
                        .then(({data}) => {

                            //  Get the analytics
                            self.analytics = (data || [])['analytics'];

                            //  Get charts
                            self.charts = self.getCharts();

                            //  Stop loader
                            self.isLoading = false;

                        })         
                        .catch(response => { 

                            //  Log the responce
                            console.error(response);

                            //  Stop loader
                            self.isLoading = false;

                        });
                }
            }
        },
        created(){

            //  Set the "Live Analytics Url" as the default Url
            this.analyticsUrl = this.project['_links']['sce:live_analytics'].href;

            //  Fetch the analytics
            this.fetchAnalytics();
            
        }
    }
</script>
