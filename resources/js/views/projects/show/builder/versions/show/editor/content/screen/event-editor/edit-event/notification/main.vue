<template>

    <div>

        <!-- Auto Reply Event Instruction -->
        <Alert type="info" style="line-height: 1.4em;" class="mb-2" closable>
            Use <span class="font-italic text-success font-weight-bold">Notification</span> 
            to show a brief message to the user.
        </Alert>

        <Row :gutter="12" class="mt-2">

            <Col :span="12">

                <!-- Notification Type -->    
                <div class="d-flex">

                    <span class="d-block font-weight-bold text-dark mt-1 mr-2">Type: </span>

                    <Select v-model="event.event_data.selected_type" placeholder="Select type" class="mr-2">

                        <Option v-for="(notification_type, key) in notification_types" :key="key"
                                :value="notification_type.value" :label="notification_type.name">
                        </Option>

                    </Select>

                </div>

            </Col>

            <template v-if="event.event_data.selected_type == 'cross_session_notification'">
            
                <Col :span="12">

                    <!-- Notification Type -->    
                    <div class="d-flex">

                        <span class="d-block font-weight-bold text-dark mt-1 mr-2">Select: </span>

                        <Select v-model="event.event_data.cross_session_notification.selected_type" placeholder="Select type" class="mr-2">

                            <Option v-for="(notification_mode, key) in notification_modes" :key="key"
                                    :value="notification_mode.value" :label="notification_mode.name">
                            </Option>

                        </Select>

                    </div>

                </Col>

            </template>

            <Col :span="24">

                <template v-if="event.event_data.selected_type == 'instant_notification'">

                    <!-- Message Input --> 
                    <textOrCodeEditor
                        size="medium"
                        class="mt-2 mb-2"
                        title="Message"
                        :value="event.event_data.instant_notification.message"
                        :placeholder="'Hello {{ first_name }}, welcome back :)'"
                        sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
                    </textOrCodeEditor>

                    <!-- Message Input --> 
                    <textOrCodeEditor
                        size="small"
                        class="mt-2 mb-2"
                        title="Continue Text"
                        :placeholder="'1. Continue'"
                        :value="event.event_data.instant_notification.continue_text"
                        sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
                    </textOrCodeEditor>

                </template>

                <template v-if="event.event_data.selected_type == 'cross_session_notification'">

                    <div class="bg-grey-light border mt-3 mb-3 pt-2 px-2 pb-2">

                        <!-- Set Notification Name -->
                        <div v-if="event.event_data.cross_session_notification.selected_type == 'set'" class="d-flex">
                            <span :style="{ width: '130px' }" class="font-weight-bold mt-1 mr-1">Notification Name: </span>
                            <Poptip trigger="hover" word-wrap class="poptip-w-100" width="300" placement="top-start"
                                    content="Set the name of this notification - This name will be used to identify this notification">
                                <Select v-model="event.event_data.cross_session_notification.set.name" filterable allow-create 
                                        @on-create="addNotificationName($event)" class="w-100" placeholder="popup notification">
                                    <Option v-for="(name, index) in existingNotificationNames" :label="name" :value="name" :key="index" :disabled="true"></Option>
                                </Select>
                            </Poptip>
                        </div>

                        <!-- Get Notification Name -->
                        <div v-if="event.event_data.cross_session_notification.selected_type == 'get'" class="d-flex mb-2">
                            <span :style="{ width: '130px' }" class="font-weight-bold mt-1 mr-1">Show Notification: </span>
                            <Poptip trigger="hover" word-wrap class="poptip-w-100"
                                    content="Select a notification to display">
                                <Select v-model="event.event_data.cross_session_notification.get.name" filterable class="w-100">
                                    <Option v-for="(name, index) in existingNotificationNames" :label="name" :value="name" :key="index"></Option>
                                </Select>
                            </Poptip>
                        </div>
                            
                    </div>

                    <template v-if="event.event_data.cross_session_notification.selected_type == 'set'">

                        <div class="bg-grey-light border mt-3 mb-3 pt-2 px-2 pb-2">

                            <!-- Message Input --> 
                            <textOrCodeEditor
                                size="medium"
                                class="mt-2 mb-2"
                                title="Message"
                                :value="event.event_data.cross_session_notification.set.message"
                                :placeholder="'Hello {{ first_name }}, welcome back :)'"
                                sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
                            </textOrCodeEditor>

                            <!-- Message Input --> 
                            <textOrCodeEditor
                                size="small"
                                class="mt-2 mb-2"
                                title="Continue Text"
                                :placeholder="'1. Continue'"
                                :value="event.event_data.cross_session_notification.set.continue_text"
                                sampleCodeTemplate="ussd_service_select_option_no_options_found_msg_sample_code">
                            </textOrCodeEditor>
                            
                        </div>

                    </template>

                </template>

            </Col>

        </Row>
        
    </div>

</template>

<script>

    import textOrCodeEditor from './../../../textOrCodeEditor.vue';

    export default {
        components: { textOrCodeEditor },
        props:{
            event: {
                type: Object,
                default:() => {}
            },
            events: {
                type: Array,
                default: () => []
            },
            display: {
                type: Object,
                default:() => {}
            },
            screen: {
                type: Object,
                default:() => {}
            },
            version: {
                type: Object,
                default: () => {}
            },
            globalMarkers: {
                type: Array,
                default: () => []
            }
        },
        data(){
            return {
                existingNotificationNames: [],
                notification_types: [
                    {
                        name: 'Instant Popup',
                        value: 'instant_notification'
                    },
                    {
                        name: 'Cross Session Popup',
                        value: 'cross_session_notification'
                    }
                ],
                notification_modes: [
                    {
                        name: 'Set Notification',
                        value: 'set'
                    },
                    {
                        name: 'Get Notification',
                        value: 'get'
                    }
                ]
            }
        },
        computed: {
        },
        methods: {
            getExistingNotificationNames(){

                console.log('start getting names');

                var names = [];

                for (let x = 0; x < this.version.builder.screens.length; x++) {
                    
                    var screen = this.version.builder.screens[x];
                    
                    //  Get notification names embedded on the screen before repeat events
                    for (let y = 0; y < screen.repeat.events.before_repeat.length; y++) {

                        var event = screen.repeat.events.before_repeat[y];

                        //  If this is a notification event
                        if( event.type == 'Notification' ){

                            //  If this is an event to Set a notification
                            if( event.event_data.selected_type == 'set' && event.event_data.cross_session_notification.set.name){
                                
                                //  Return the notification name
                                names.push(event.event_data.cross_session_notification.set.name);

                            }
                            
                        }
                        
                    }
                    
                    //  Get notification names embedded on the screen after repeat events
                    for (let y = 0; y < screen.repeat.events.after_repeat.length; y++) {

                        var event = screen.repeat.events.after_repeat[y];

                        //  If this is a notification event
                        if( event.type == 'Notification' ){

                            //  If this is an event to Set a notification
                            if( event.event_data.selected_type == 'set' && event.event_data.cross_session_notification.set.name){
                                
                                //  Return the notification name
                                names.push(event.event_data.cross_session_notification.set.name);

                            }
                            
                        }
                        
                    }

                    for (let y = 0; y < screen['displays'].length; y++) {
                        
                        var display = screen['displays'][y];
                        
                        //  Get notification names embedded on the display before reply events
                        for (let z = 0; z < display.content.events.before_reply.length; z++) {

                            var event = display.content.events.before_reply[z];

                            //  If this is a notification event
                            if( event.type == 'Notification' ){

                                //  If this is an event to Set a notification
                                if( event.event_data.selected_type == 'set' && event.event_data.cross_session_notification.set.name){
                                    
                                    //  Return the notification name
                                    names.push(event.event_data.cross_session_notification.set.name);

                                }
                                
                            }
                            
                        }
                        
                        //  Get notification names embedded on the display after reply events
                        for (let z = 0; z < display.content.events.after_reply.length; z++) {

                            var event = display.content.events.after_reply[z];

                            //  If this is a notification event
                            if( event.type == 'Notification' ){

                                //  If this is an event to Set a notification
                                if( event.event_data.selected_type == 'set' && event.event_data.cross_session_notification.set.name){
                                    
                                    //  Return the notification name
                                    names.push(event.event_data.cross_session_notification.set.name);

                                }
                                
                            }
                            
                        }
                    }

                }

                console.log('names');
                console.log(names);
                
                return names;

            },
            addNotificationName(newName){

                //  Check if this notification name already exists
                var similarNamesExist = this.existingNotificationNames.filter( (existingName) => {

                    //  CCheck if the names match
                    return (existingName == newName);

                }).length ? true : false;

                console.log('similarNamesExist');
                console.log(similarNamesExist);

                //  If we do not have any other notification already using this name
                if( !similarNamesExist ){
                   
                    //  If we do not have any other notification already using this name
                    this.event.event_data.cross_session_notification.set.name = newName; 

                    //  Get the existing notification names
                    this.existingNotificationNames = this.getExistingNotificationNames();

                }else{
                    
                    this.$Message.warning({
                        content: 'Sorry, you cannot use an existing notification name',
                        duration: 6
                    });

                }

            }
        },
        created() {

            //  Get the existing notification names
            this.existingNotificationNames = this.getExistingNotificationNames();

        },
    }
</script>