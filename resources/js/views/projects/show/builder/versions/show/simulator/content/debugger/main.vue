<template>

    <div>

        <Divider orientation="left">
            <span class="text-primary">Debugger</span>
        </Divider>
                    
        <!-- Show first display checkbox (Marks the display as the first display) -->
        <Checkbox v-model="version.builder.simulator.debugger.return_logs" class="d-flex mb-3">
            <span class="mx-1">Return Logs (Slower performance)</span>
            <Poptip trigger="hover" placement="right-start" word-wrap width="250" 
                    content="Return logs from the simulator to retrieve information, warnings and errors while testing your application. Retrieving logs will slow down your requests, therefore use this feature mostly for debugging and diagnosing application warnings and errors">
                <Icon type="ios-information-circle-outline" :size="20" /> 
            </Poptip>   
        </Checkbox>

        <template v-if="version.builder.simulator.debugger.return_logs">

            <!-- Loader -->
            <Loader v-if="ussdSimulatorLoading" class="text-left mt-3">USSD Code running</Loader>

            <template v-if="ussdSimulatorResponse && !ussdSimulatorLoading">

                <div class="d-flex">
                    <span class="font-weight-bold text-dark mt-1 mr-2">Show:</span>
                    <Select v-model="selectedLogType" filterable placeholder="Filter logs" class="mb-4">

                        <Option 
                            v-for="(log, key) in logTypes"
                            :key="key" :value="log" :label="log">
                        </Option>

                    </Select>
                </div>

                <div class="bg-grey-light border p-2">
                    <span v-if="['All', 'Info'].includes(selectedLogType)" class="mr-2">
                        {{ ussdSimulatorInfoLogsTotal }} Info
                    </span>
                    <span v-if="['All', 'Warnings'].includes(selectedLogType)" class="mr-2">
                        {{ ussdSimulatorWarningLogsTotal }}
                        {{ ussdSimulatorWarningLogsTotal == 1 ? ' Warning' : ' Warnings' }}
                    </span>
                    <span v-if="['All', 'Errors'].includes(selectedLogType)" class="mr-2">
                        {{ ussdSimulatorErrorLogsTotal }}
                        {{ ussdSimulatorErrorLogsTotal == 1 ? ' Error' : ' Errors' }}
                    </span>
                </div>

                <Timeline :style="{ minHeight: '200px', maxHeight:'400px', overflowY: 'auto' }" class="py-3 pl-1">

                    <TimelineItem v-for="(log, index) in selectedLogsToDisplay" :key="index"
                        :color="getLogDotColor(log.type)">

                        <div class="d-flex">
                            
                            <!-- Show bug icon on error log -->
                            <Icon v-if="log.type == 'error'" type="ios-bug-outline" slot="dot" :size="20" />

                            <!-- Searching for first screen/display icon -->
                            <Icon v-if="['searching_first_screen', 'searching_first_display'].includes(log.data_type)" type="ios-search" class="text-success" :size="20" /> 

                            <!-- Selected screen/display icon -->
                            <Icon v-if="['selected_screen', 'selected_display'].includes(log.data_type)" type="ios-pin-outline" class="text-success" :size="20" /> 
               
                            <!-- If value is JSON -->
                            <pre v-if="isJson(log.description)"
                                v-html="convertToJson(log.description)" :class="[
                                    (log.type == 'error' ? 'text-danger' : ''), 'bg-light border', 'rounded',  'p-2'
                                ]">
                            </pre>

                            <!-- If value is not JSON -->
                            <span v-else
                                v-html="log.description"
                                :class="log.type == 'error' ? 'text-danger' : ''">
                            </span>

                            <template v-if="log.data_type == 'dynamic_variables'">

                                <Poptip trigger="hover" word-wrap width="300">

                                    <div slot="content" class="py-2" :style="{ lineHeight: 'normal' }">
                                        <p v-for="(dynamic_variable, index) in log.data.dynamic_variables" :key="index">
                                            <span class="font-weight-bold">{{ dynamic_variable.name }}: </span> 
                                            <span v-html="dynamic_variable.data_type"></span>

                                            <Poptip trigger="hover" :content="dynamic_variable.value" 
                                                    placement="right" word-wrap width="300">
                                                
                                                <Icon type="ios-information-circle-outline" :size="20" /> 

                                                <div slot="content">

                                                    <!-- If value is JSON -->
                                                    <pre v-if="isJson(dynamic_variable.value)"
                                                        v-html="convertToJson(dynamic_variable.value)" :class="[
                                                            'bg-light border', 'rounded',  'p-2'
                                                        ]">
                                                    </pre>

                                                    <!-- If value is not JSON -->
                                                    <span v-else v-html="dynamic_variable.value"></span>

                                                </div>

                                            </Poptip>
                                        </p>
                                    </div>

                                    <Icon type="ios-information-circle-outline" :size="20" /> 

                                </Poptip>
                                
                            </template>

                        </div>

                    </TimelineItem>

                </Timeline>

            </template>

            <!-- No simulator response -->
            <Alert v-if="!ussdSimulatorResponse && !ussdSimulatorLoading" type="info" show-icon>
                Run the simulator to test your application 
            </Alert>

        </template>

    </div>
    
</template>

<script>

    /*  Loaders  */
    import Loader from'./../../../../../../../../../components/_common/loaders/default.vue';  

    export default {
        components: { Loader },
        props: {
            project: {
                type: Object,
                default: null
            },
            version: {
                type: Object,
                default: null
            },
            ussdSimulatorLoading: {
                type: Boolean,
                default: false
            },
            ussdSimulatorResponse: {
                type: Object,
                default: null
            }
        },
        data(){
            return {
                logTypes: ['All', 'Info', 'Warnings', 'Errors'],
                selectedLogType: 'All',
            }
        }, 
        computed: {
            ussdSimulatorInfoLogs(){
                return ((this.ussdSimulatorResponse || {}).logs || []).filter( (log) => { 
                    if(log.type == 'info'){
                        return log;
                    }
                }) || [];
            },
            ussdSimulatorInfoLogsTotal(){
                return this.ussdSimulatorInfoLogs.length;
            },
            ussdSimulatorWarningLogs(){
                return ((this.ussdSimulatorResponse || {}).logs || []).filter( (log) => { 
                    if(log.type == 'warning'){
                        return log;
                    }
                }) || [];
            },
            ussdSimulatorWarningLogsTotal(){
                return this.ussdSimulatorWarningLogs.length;
            },
            ussdSimulatorErrorLogs(){
                return ((this.ussdSimulatorResponse || {}).logs || []).filter( (log) => { 
                    if(log.type == 'error'){
                        return log;
                    }
                }) || [];
            },
            ussdSimulatorErrorLogsTotal(){
                return this.ussdSimulatorErrorLogs.length;
            },
            selectedLogsToDisplay(){

                if(this.selectedLogType == 'All'){

                    var type = ['info', 'warning', 'error'];

                }else if(this.selectedLogType == 'Info'){

                    var type = ['info'];

                }else if(this.selectedLogType == 'Warnings'){

                    var type = ['warning'];

                }else if(this.selectedLogType == 'Errors'){

                    var type = ['error'];

                }

                return ((this.ussdSimulatorResponse || {}).logs || []).filter( (log) => { 
                    if( type.includes( log.type ) ){
                        return log;
                    }
                });
            }
        },
        methods: {
            isJson(value) {

                try {
                    if (typeof value === "object" && value !== null) {
                        return true;
                    }else{
                        var item = JSON.parse(value);
                    }
                } catch (e) {
                    return false;
                }

                if (typeof item === "object" && item !== null) {
                    return true;
                }

                return false;
            },
            convertToJson(value) {
                //  If the value is already a JSON Object
                if (typeof value === "object" && value !== null) {

                    //  Return value as it is
                    return value;

                }else{

                    //  Convert string to valid JSON
                    return JSON.parse(value);
                
                } 
            },
            getLogDotColor(type){
                if( type == 'info' ){
                    return 'green';
                }else if( type == 'warning' ){
                    return '#ffa300';
                }else if( type == 'error' ){
                    return 'red';
                }else{
                    return '#909090';
                }
            }
        }
    };
  
</script>