<template>

    <Row :gutter="4">

        <Col :span="24" class="mb-2">

            <!-- Test Event API Instructions -->
            <Alert v-if="!isTestingApiUrl && !testAPIResponse" 
                    type="info" style="line-height: 1.4em;" class="mb-2" closable>
                Enter the <span class="font-italic text-success font-weight-bold">API Method</span>
                and <span class="font-italic text-success font-weight-bold">API URL</span>, then
                hit <span class="font-weight-bold text-success">Test API</span> to get a response.
            </Alert>

        </Col>

        <Col :span="24" class="mb-2">

            <div class="d-flex mb-3">

                <span class="font-weight-bold mt-1 mr-2">Method: </span>

                <!-- Select Event API Method -->
                <Select v-model="event.event_data.method" class="w-50">
                    <Option value="get">GET</Option>
                    <Option value="post">POST</Option>
                    <Option value="patch">PATCH</Option>
                    <Option value="delete">DELETE</Option>
                </Select>

            </div>

            <textOrCodeEditor
                class="mb-2"
                size="small"
                title="Api Url"
                :value="event.event_data.url"
                :placeholder="'https://www.example.com/api/users/{{ user.id }}/messages'"
                sampleCodeTemplate="ussd_service_select_option_display_name_sample_code">
            </textOrCodeEditor>

            <div class="clearfix">

                <!-- Test Event API Button -->
                <Button
                    :disabled="isTestingApiUrl || event.event_data.url.code_editor_mode || !event.event_data.url.text"
                    class="float-right" type="success" @click.native="testApi()">
                    <Icon type="ios-repeat" :size="20" />
                    <span>Test Api</span>
                </Button>

            </div>

        </Col>

        <Col :span="24">

            <!-- Test API loader -->
            <Loader v-if="isTestingApiUrl">Requesting...</Loader>

            <!-- Test API Response -->
            <template v-if="testAPIResponse">

                <!-- Test API Response Navigation Tabs -->
                <Tabs v-model="activeNavTab" type="card" :style="{ overflow: 'visible' }" :animated="false">

                    <!-- Test API Response Tab -->
                    <TabPane v-for="(currentTabName, key) in navTabs" :key="key" :label="currentTabName" :name="currentTabName"></TabPane>

                </Tabs>
                
                <!-- Test API Response Data --> 
                <div class="bg-grey-light border p-2">

                    <span v-if="testAPIResponse.status" class="d-inline-block mr-4">
                        <span class="font-weight-bold text-dark">Status: </span>
                        <span :class="(testAPIResponse.status == '200' ? 'text-success' : 'text-danger')">
                            {{ testAPIResponse.status }}
                        </span>
                    </span>

                    <span v-if="testAPIResponse.statusText" class="d-inline-block">
                        <span class="font-weight-bold text-dark">Status Text: </span>
                        <span>{{ testAPIResponse.statusText }}</span>
                    </span>

                </div>

                <!-- Test API Response Body --> 
                <template v-if="activeNavTab == 'Body'">
                    
                    <div class="mt-2">

                        <!-- API Response Data -->
                        <div v-if="(testAPIResponse || {}).status" class="border">

                            <div class="clearfix bg-grey-light border-bottom p-2">
                                <span class="d-block font-weight-bold text-dark float-left">Response Data</span>
                                <Badge :text="((testAPIResponse || {}).status || '').toString()" class="float-right"
                                        :status="( (testAPIResponse || {}).status == '200' ? 'success' : 'error')">
                                </Badge>
                            </div>

                            <!-- Code Editor -->
                            <codemirror 
                                v-if="testAPIResponsePrettierFormat"
                                v-model="testAPIResponsePrettierFormat" 
                                :options="{ mode: 'application/json', readOnly: true }">
                            </codemirror>
                            
                        </div>
                        
                    </div>

                </template>

                <!-- Test API Response Headers --> 
                <template v-if="activeNavTab == 'Headers'">

                    <div class="p-2">

                        <template v-if="(testAPIResponse || {}).headers">
                            
                            <div v-for="(header_value, header_name) in (testAPIResponse || {}).headers" 
                                :key="header_name" class="mb-2">
                                <span class="font-weight-bold text-capitalize text-dark">{{ header_name }}: </span>
                                <span class="text-success text-break">{{ header_value }}</span>
                            </div>

                        </template>

                        <Divider class="d-block mt-2 mb-2" />

                        <template v-if="((testAPIResponse || {}).config || {}).headers">
                            
                            <div v-for="(header_value, header_name) in ((testAPIResponse || {}).config || {}).headers" 
                                :key="header_name" class="mb-2">
                                <span class="font-weight-bold text-capitalize text-dark">{{ header_name }}: </span>
                                <span class="text-success text-break">{{ header_value }}</span>
                            </div>

                        </template>

                    </div>

                </template>

            </template>

        </Col>

    </Row>

</template>

<script>

    // Import our custom codemirror component
    import codemirror from './../../../../../../../../../../../../../../components/_common/wysiwygEditors/codemirror.vue';

    //  Get the loader
    import Loader from './../../../../../../../../../../../../../../components/_common/loaders/default.vue';

    import textOrCodeEditor from './../../../../../textOrCodeEditor.vue';

    export default {
        components: { codemirror, Loader, textOrCodeEditor },
        props: {
            index: {
                type: Number,
                default: null
            },
            event: {
                type: Object,
                default: null
            },
            events: {
                type: Array,
                default: () => []
            },
            screen: {
                type: Object,
                default: null
            },
            display: {
                type: Object,
                default: null
            },
            builder: {
                type: Object,
                default: null
            },
            isCloning: {
                type: Boolean,
                default: false
            },
            isEditing: {
                type: Boolean,
                default: false
            },
        },
        data(){
            return{
                isTestingApiUrl: false,
                testAPIResponse: null,
                activeNavTab: 'Body'
            }
        },
        computed: {

            navTabs(){
                var tabs = ['Body', 'Headers'];

                return tabs;
            },
            testAPIResponsePrettierFormat(){

                return JSON.stringify((this.testAPIResponse || {}).data, undefined, 2);

            }

        },
        methods: {
            testApi(){
                
                //  If we have a method and the url and are not using code editor mode
                if(this.event.event_data.method && !this.event.event_data.url.code_editor_mode && this.event.event_data.url.text){

                    //  Hold constant reference to the vue instance
                    const self = this;

                    //  Start loader
                    this.isTestingApiUrl = true;

                    //  Reset the API test data and error log
                    this.testAPIResponse = null;

                    //  Use the api call() function located in resources/js/api.js
                    api.call(this.event.event_data.method, this.event.event_data.url.text)
                        .then((response) => {

                            console.log(response);

                            //  Stop loader
                            self.isTestingApiUrl = false;
                            
                            self.testAPIResponse = response;

                            self.selectedRequestResponseTab = ((response || {}).status || {}).toString();

                        })         
                        .catch(({response}) => {

                            console.log(response);

                            //  Stop loader
                            self.isTestingApiUrl = false;
                            
                            self.testAPIResponse = response;

                            self.selectedRequestResponseTab = ((response || {}).status || {}).toString();

                        });

                }

            }
        }
    };
  
</script>