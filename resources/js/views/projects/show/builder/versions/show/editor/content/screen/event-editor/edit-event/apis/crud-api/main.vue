<template>

    <div>

        <!-- Event Settings -->
        <div class="mt-2">

            <Tabs v-model="activeNavTab" type="card" style="overflow: visible;" :animated="false" name="crud-api-event-tabs">

                <!-- Screen Settings Navigation Tabs -->
                <TabPane v-for="(currentTabName, key) in navTabs" :key="key" :label="currentTabName" :name="currentTabName"></TabPane>

            </Tabs>

            <!-- Request Url -->
            <requestUrl v-show="activeNavTab == 'Request Url'" v-bind="$props"></requestUrl>

            <!-- Query Params -->
            <requestQueryParams v-show="activeNavTab == 'Query Params'" v-bind="$props"></requestQueryParams>

            <!-- Form Data -->
            <requestFormData v-show="activeNavTab == 'Form Data'" v-bind="$props"></requestFormData>
                
            <!-- Headers -->
            <requestHeaders v-show="activeNavTab == 'Headers'" v-bind="$props"></requestHeaders>
                
            <!-- Responses -->
            <requestResponses v-show="activeNavTab == 'Responses'" v-bind="$props"></requestResponses>

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
        components: {
            requestUrl, requestQueryParams, requestFormData, requestHeaders, requestResponses
        },
        data(){
            return{
                localEvent: this.event,
                activeNavTab: 'Request Url'
            }
        }, 
        computed: {
            navTabs(){
                var tabs = ['Request Url', 'Query Params', 'Form Data', 'Headers', 'Responses'];

                return tabs;
            }
        },
        created(){

        }
    }
</script>