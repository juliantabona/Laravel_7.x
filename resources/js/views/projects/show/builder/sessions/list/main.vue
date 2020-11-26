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

                    <h1 class="text-center py-3 mb-3">Sessions</h1>

                </Col>

                <Col :span="8" class="clearfix">

                    <div class="float-right mb-3 py-3">
                            
                        <span class="d-inline-block mr-2">Type:</span>

                        <Select v-model="sessionsUrl" style="width:200px" @on-change="fetchSessions()">
                            <Option v-for="endpoint in sessionEndpoints" :value="endpoint.url" :key="endpoint.name">
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
                                @click.native="fetchSessions()" :loading="isLoading" 
                                :disabled="isLoading">
                            <Icon v-show="!isLoading" type="ios-refresh" class="mr-1" :size="20" />
                            <span>Refresh</span>
                        </Button>
                    
                    </div>

                    <Table :columns="columns" :data="sessions" :loading="isLoading"></Table>

                    <div :style="{ margin: '10px', overflow: 'hidden' }">
                        <div :style="{ float: 'right' }">
                            <Page :total="100" :current="1" @on-change="changePage"></Page>
                        </div>
                    </div>

                </Col>

            </Row>

        </Col>

    </Row>

</template>

<script>

    import moment from 'moment';

    export default {
        props: {
            project: {
                type: Object,
                default: null
            }
        },
        data(){
            return {
                columns: [
                    {
                        title: 'ID',
                        key: 'session_id'
                    },
                    {
                        title: 'Mobile',
                        key: 'msisdn'
                    },
                    {
                        title: 'Duration',
                        key: 'total_session_duration',
                        render: (h, params) => {

                            var mins, secs, duration;

                            secs = parseInt(params.row.total_session_duration);

                            if( secs > 60 ){

                                //  Calculate the minutes
                                mins = Math.floor(secs / 60);

                                //  Calculate the seconds left
                                secs = secs - mins * 60;

                                if(secs == 0){

                                    duration = mins + (mins == 1 ? ' min' : ' mins');

                                }else{

                                    duration = mins + (mins == 1 ? ' min' : ' mins') + ' ' + secs + (secs == 1 ? ' sec' : ' secs');

                                }

                            }else{

                                duration = secs + (secs == 1 ? ' sec' : ' secs');

                            }

                            return h('span', duration);
                        }
                    },
                    {
                        title: 'Last Active',
                        width: 200,
                        key: 'updated_at',
                        render: (h, params) => {

                            var updated_at = moment(params.row.updated_at).format("DD MMM YYYY @ HH:mm");

                            return h('span', updated_at);
                        }
                    },
                    {
                        title: 'Status',
                        key: 'fatal_error',
                        render: (h, params) => {

                            var status = params.row.status;
                            var name = status.name;
                            var desc = status.desc;
                            var color = 'default';

                            if( name === 'Fail'){

                                color = 'error';

                            }else if( name === 'Timeout'){

                                color = 'warning';

                            }else if( name === 'Closed'){

                                color = 'default';

                            }else if( name === 'Active'){

                                color = 'success';

                            }

                            return h('Tag', {
                                props: {
                                    type: 'dot',
                                    color: color
                                }
                            }, name);
                        }
                    },
                    {
                        title: 'Action',
                        render: (h, params) => {

                            return h('div', {
                                class: ['clearfix']
                            },[
                                h('Button', {
                                    props: {
                                        type: 'default',
                                        size: 'small'
                                    }
                                }, 'View')
                            ]);

                        }
                    },
                ],
                isLoading: false,
                sessionsUrl: null,
                sessions: []
            }
        },
        computed: {
            sessionEndpoints(){
                return [
                    { 
                        name: 'All Sessions', 
                        url: this.project['_links']['sce:sessions'].href
                    },
                    { 
                        name: 'Live Sessions', 
                        url: this.project['_links']['sce:live_sessions'].href
                    },
                    { 
                        name: 'Test Sessions', 
                        url: this.project['_links']['sce:test_sessions'].href
                    }
                ];
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
            changePage(){

            },
            fetchSessions() {

                //  If we have the sessions url
                if( this.sessionsUrl ){

                    //  Hold constant reference to the current Vue instance
                    const self = this;

                    //  Start loader
                    self.isLoading = true;

                    console.log('Fetch sessions');

                    //  Use the api call() function, refer to api.js
                    api.call('get', this.sessionsUrl)
                        .then(({data}) => {

                            //  Get the sessions
                            self.sessions = ((data || [])['_embedded'] || [])['ussd_sessions'];

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

            //  Set the "Live Session Url" as the default Url
            this.sessionsUrl = this.project['_links']['sce:live_sessions'].href;

            //  Fetch the sessions
            this.fetchSessions();
            
        }
    }
</script>
