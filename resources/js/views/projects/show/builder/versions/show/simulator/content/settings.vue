<template>

    <div>

        <Divider orientation="left">
            <span class="text-primary">Settings</span>
        </Divider>

        <!-- Screen name input (Changes the screen name) -->  
        <Input type="text" v-model="version.builder.simulator.subscriber.phone_number" placeholder="26772334455" maxlength="11" class="mb-3">
                <span slot="prepend">Mobile Number</span>
        </Input>
                    
        <!-- Show first display checkbox (Marks the display as the first display) -->
        <Checkbox v-model="version.builder.simulator.settings.allow_timeouts" class="mb-3">Allow Timeouts</Checkbox>

        <template v-if="version.builder.simulator.settings.allow_timeouts">
            
            <div class="d-flex mb-3">

                <span class="font-weight-bold mt-1 mr-1">Timeout: </span>
                
                <InputNumber v-model.number="version.builder.simulator.settings.timeout_limit_in_seconds" 
                        :min="60" :max="120" placeholder="Timeout limit..." class="w-100">
                </InputNumber>

            </div>

            <Input type="textarea" v-model="version.builder.simulator.settings.timeout_message" class="mb-3"></Input> 

        </template>   

    </div>
    
</template>

<script>

    /*  Loaders  */
    import Loader from'./../../../../../../../../components/_common/loaders/default.vue';  

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