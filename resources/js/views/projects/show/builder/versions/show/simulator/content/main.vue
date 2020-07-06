<template>

    <Row :gutter="10">

        <Col :span="12">

            <Card>

                <Divider orientation="left">
                    <span class="text-primary">Debugger</span>
                </Divider>
    
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

                    <Timeline style="max-height:200px; overflow-y:auto;" class="py-3 pl-1">

                        <TimelineItem v-for="(log, index) in selectedLogsToDisplay" :key="index"
                            :color="getLogDotColor(log.type)">

                            <!-- Show bug icon on error log -->
                            <Icon v-if="log.type == 'error'" type="ios-bug-outline" slot="dot" :size="20" />

                            <span 
                                v-html="log.description"
                                :class="log.type == 'error' ? 'text-danger' : ''">
                            </span>

                        </TimelineItem>

                    </Timeline>

                </template>

                <!-- No simulator response -->
                <Alert v-if="!ussdSimulatorResponse && !ussdSimulatorLoading" type="info" show-icon>
                    Run the simulator to test your application 
                </Alert>
            </Card>

        </Col>

        <!-- USSD Simulator -->
        <Col :span="12">
        
            <ussdSimulator 
                :project="project"
                :version="version"
                @loading="ussdSimulatorLoading = $event"
                @response="ussdSimulatorResponse = $event">
            </ussdSimulator>
            
        </Col>

    </Row>
    
</template>

<script>

    /*  Loaders  */
    import Loader from'./../../../../../../../../components/_common/loaders/default.vue';  

    /*  Buttons  */
    import ussdSimulator from './../../../../../../../../components/_common/simulators/ussdSimulator.vue';

    export default {
        components: { Loader, ussdSimulator },
        props: {
            project: {
                type: Object,
                default: null
            },
            version: {
                type: Object,
                default: null
            }
        },
        data(){
            return {
                logTypes: ['All', 'Info', 'Warnings', 'Errors'],
                builder: this.version.builder,
                ussdSimulatorLoading: false,
                ussdSimulatorResponse: null,
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