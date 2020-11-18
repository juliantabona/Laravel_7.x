<template>

    <div>

        <Row :gutter="12" class="my-4">

            <Col :span="8" v-for="(chart, index) in charts" :key="index">
                
                <Card>

                    <h4 class="font-weight-bold text-dark">{{ chart.title }}</h4>
                    
                    <Divider class="mt-2 mb-2"></Divider>

                    <span :style="{ fontSize: '12px', textAlign: 'justify' }" class="d-block">
                        {{ chart.desc }}
                    </span>

                    <Divider class="mt-2 mb-2"></Divider>

                    <lineChart :chartdata="chart.chartData" :options="chartOptions" :style="chartStyles"/>

                    <Card v-if="chart.title == 'Reply Durations'" title="Summary">
                        
                        <span v-if="averageUserResponseDuration" class="d-block mb-2">
                            Average Response: <span :class="responseDurationClasses(maxUserResponseDuration)">{{ averageUserResponseDuration + (averageUserResponseDuration == 1 ? ' Second' : ' Seconds') }}</span>
                        </span>
                        <span v-if="minUserResponseDuration" class="d-block mb-2">
                            Fastest Response: <span :class="responseDurationClasses(maxUserResponseDuration)">{{ minUserResponseDuration + (minUserResponseDuration == 1 ? ' Second' : ' Seconds') }}</span>
                        </span>
                        <span v-if="maxUserResponseDuration" class="d-block">
                            Slowest Response: <span :class="responseDurationClasses(maxUserResponseDuration)">{{ maxUserResponseDuration + (maxUserResponseDuration == 1 ? ' Second' : ' Seconds') }}</span>
                        </span>

                    </Card>

                </Card>

            </Col>

        </Row>

    </div>

</template>

<script>

    import lineChart from './../../../../../../../../components/_common/charts/lineChart.vue';

    export default {
        components: { lineChart },
        props: {
            project: {
                type: Object,
                default: null
            },
            version: {
                type: Object,
                default: null
            },
            activeView: {
                type: String,
                default: ''
            },
            ussdSimulatorResponse: {
                type: Object,
                default: null
            },
            ussdSimulatorLoading: {
                type: Boolean,
                default: false
            },
        },
        data(){
            return {
                charts: null,
                chartOptions: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true
                            }
                        }],
                        xAxes: [{
                            ticks: {
                                display: false      //  this will remove only the x-axis label (at the bottom)
                            }
                        }]
                    }
                },
                chartStyles: {
                    height: '250px'
                }
            }
        },
        watch: {
            //  Watch for changes on the ussdSimulatorResponse
            ussdSimulatorResponse: {
                handler: function (newVal, oldVal) {
                    this.charts = this.getCharts();
                },
                deep: true
            }
        },
        computed: {
            stats(){
                return ((this.ussdSimulatorResponse || {}).stats || {});
            },
            minUserResponseDuration(){
                return ((this.stats || {}).user_response_durations || {}).min;
            },
            maxUserResponseDuration(){
                return ((this.stats || {}).user_response_durations || {}).max;
            },
            averageUserResponseDuration(){
                return ((this.stats || {}).user_response_durations || {}).average;
            }
        },
        methods: {   
            getCharts() {
                return [
                    {
                        title: 'Session Execution Time',
                        desc: 'This measures the time it takes to handle a single session request in seconds. If the session request time is long then the service is performing poorly, whereas if the session request time is short then the service is pereforming well. Each session request must aim to have the shortest request time.',
                        chartData: {
                            labels: ((this.stats || {}).session_execution_times || []).map( (data) => {
                                    return data.recorded_at
                                }),
                            datasets: [
                                {
                                    label: 'Session Execution Time (In Seconds)',
                                    borderColor: '#008b00',
                                    fill: false,              // Remove background color below the line
                                    data: ((this.stats || {}).session_execution_times || []).map( (data) => {
                                        return data.time
                                    })
                                }
                            ]
                        }
                    },
                    {
                        title: 'Session Record Size',
                        desc: 'This measures the growing size in kilobytes of the current session. The size represents the data collected and generated in order to create and maintain the currently running session. The size grows as the session collects data from user responses, past sessions and debugging logs (in the cases of errors).',
                        chartData: {
                            labels: ((this.stats || {}).estimated_record_sizes || []).map( (data) => {
                                    return data.recorded_at
                                }),
                            datasets: [
                                {
                                    label: 'Session Record Size (In Kilobytes)',
                                    borderColor: '#61d800',
                                    fill: false,              // Remove background color below the line
                                    data: ((this.stats || {}).estimated_record_sizes || []).map( (data) => {
                                        return data.size
                                    })
                                }
                            ]
                        }
                    },
                    {
                        title: 'Reply Durations',
                        desc: 'This measures the duration between the user replies in seconds. If the duration is short, then this means that the user is replying fast, whereas if the duration is long then the user is replying slowly. Each response must not exceed 120 seconds otherwise the user will risk being timed out from their current session.',
                        chartData: {
                            labels: (((this.stats || {}).user_response_durations || {}).records || []).map( (data) => {
                                    return data.replied_at
                                }),
                            datasets: [
                                {
                                    label: 'Reply Durations (In Seconds)',
                                    borderColor: '#61d800',
                                    fill: false,              // Remove background color below the line
                                    data: (((this.stats || {}).user_response_durations || {}).records || []).map( (data) => {
                                        return data.duration
                                    })
                                }
                            ]
                        }
                    },
                    /*
                    {
                        title: 'Session Record Size',
                        desc: 'This measures the growing size in kilobytes of the current session. The size represents the data collected and generated in order to create and maintain the currently running session. The size grows as the session collects data from user responses, past sessions and debugging logs (in the cases of errors).',
                        data: []
                    },
                    {
                        title: 'Reply Durations',
                        desc: 'This measures the duration between the user replies in seconds. If the duration is short, then this means that the user is replying fast, whereas if the duration is long then the user is replying slowly. Each response must not exceed 120 seconds otherwise the user will risk being timed out from their current session.',
                        data: ((ussdSimulatorResponse || {}).stats || {}).user_response_durations
                    }
                    */
                ]
            },
            responseDurationClasses(seconds){
                return [(seconds >= 120 ? 'text-danger' : 'text-success'),'font-weight-bold mb-2'];
            }
        }
    }
</script>
