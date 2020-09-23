<template>

    <div>

        <!-- Event Settings -->
        <div class="mt-2">

            <Tabs v-model="activeNavTab" type="card" style="overflow: visible;" :animated="false" name="crud-api-event-tabs">

                <!-- Screen Settings Navigation Tabs -->
                <TabPane v-for="(currentTabName, key) in navTabs" :key="key" :label="currentTabName.name" :name="currentTabName.value"></TabPane>

            </Tabs>

            <!-- Request Url -->
            <requestUrl v-show="activeNavTab == '1'" v-bind="$props"></requestUrl>

            <!-- Query Params -->
            <requestQueryParams v-show="activeNavTab == '2'" v-bind="$props"></requestQueryParams>

            <!-- Form Data -->
            <requestFormData v-show="activeNavTab == '3'" v-bind="$props"></requestFormData>
                
            <!-- Headers -->
            <requestHeaders v-show="activeNavTab == '4'" v-bind="$props"></requestHeaders>
                
            <!-- Responses -->
            <requestResponses v-show="activeNavTab == '5'" v-bind="$props"></requestResponses>

        </div>
    </div>

</template>

<script>
    
    //  Get the Request URL Settings
    import requestUrl from './request-url/main.vue';

    //  Get the Request Query Params Settings
    import requestQueryParams from './request-query-params/main.vue';

    //  Get the Request Form Data Settings
    import requestFormData from './request-form-data/main.vue';

    //  Get the Request Form Data Settings
    import requestHeaders from './request-headers/main.vue';

    //  Get the display editor
    import requestResponses from './request-response/main.vue';

    export default {
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
            version: {
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
        components: {
            requestUrl, requestQueryParams, requestFormData, requestHeaders, requestResponses
        },
        data(){
            return{
                activeNavTab: '1',
                localEvent: this.event,
            }
        }, 
        computed: {
            queryParamsTabName(){
                
                 var tabName = 'Query Params';
                 var total = this.event.event_data.query_params.length;

                if( total ){
                    tabName += ' ('+total+')';
                }

                return tabName;
            },
            formDataTabName(){
                
                 var tabName = 'Form Data';
                 var total = this.event.event_data.form_data.params.length;

                if( !this.event.event_data.form_data.use_custom_code && total ){
                    tabName += ' ('+total+')';
                }

                return tabName;
            },
            headersTabName(){
                
                 var tabName = 'Headers';
                 var total = this.event.event_data.headers.length;

                if( total ){
                    tabName += ' ('+total+')';
                }

                return tabName;
            },
            navTabs(){
                return [
                    { name: 'Request Url', value: '1' },
                    { name: this.queryParamsTabName, value: '2' },
                    { name: this.formDataTabName, value: '3' },
                    { name: this.headersTabName, value: '4' },
                    { name: 'Responses', value: '5' }
                ];
            }
        },
        created(){

        }
    }
</script>