<template>
    <div>
        <!-- Modal 

             Note: modalVisible and detectClose() are imported from the modalMixin.
             They are used to allow for opening and closing the modal properly
             during the v-if conditional statement of the parent component. It
             is important to note that <Modal> does not open/close well with
             v-if statements by default, therefore we need to add additional
             functionality to enhance the experience. Refer to modalMixin.
        -->
        <Modal
            :width="modalWidth"
            :title="modalTitle"
            v-model="modalVisible"
            @on-visible-change="detectClose">

            <Alert v-if="isEditing" show-icon>Editing</Alert>

            <Alert v-else-if="isCloning" show-icon>Cloning</Alert>
            
            <!-- Form -->
            <Form ref="eventForm" class="mb-2" :model="eventForm" :rules="eventFormRules">

                <Row :gutter="12">

                    <Col :span="firstRowSpan">

                        <Checkbox v-if="!usingGlobalEventManager" v-model="eventForm.global">
                            <span class="font-weight-bold">Global Event</span>
                        </Checkbox>

                    </Col>

                    <Col :span="firstRowSpan">

                        <!-- Show active state checkbox (Marks if this is active / inactive) -->
                        <activeStateSelector v-model="eventForm.active" class="mb-2"></activeStateSelector>

                    </Col>

                    <Col :span="24">

                        <!-- Enter Name -->
                        <FormItem prop="name" class="highlight-form-item-input mb-2">
                            <Input  type="text" v-model="eventForm.name" placeholder="Event name" maxlength="50" show-word-limit>
                                    <span slot="prepend">Name</span>
                            </Input>
                        </FormItem>

                    </Col>

                </Row>

            </Form>
            
            <!-- Edit CRUD API Event --> 
            <editCrudApiEvent v-if="eventForm.type == 'CRUD API'" v-bind="$props" :event="eventForm"></editCrudApiEvent>
            
            <!-- Edit BILLING API Event --> 
            <editBillingApiEvent v-if="eventForm.type == 'Billing API'" v-bind="$props" :event="eventForm"></editBillingApiEvent>
            
            <!-- Edit Notification Event --> 
            <editNotificationEvent v-if="eventForm.type == 'Notification'" v-bind="$props" :event="eventForm"></editNotificationEvent>

            <!-- Edit Validation Event --> 
            <editValidationEvent v-if="eventForm.type == 'Validation'" v-bind="$props" :event="eventForm"></editValidationEvent>

            <!-- Edit Formatting Event --> 
            <editFormattingEvent v-if="eventForm.type == 'Formatting'" v-bind="$props" :event="eventForm"></editFormattingEvent>

            <!-- Edit Local Storage Event --> 
            <editLocalStorageEvent v-if="eventForm.type == 'Local Storage'" v-bind="$props" :event="eventForm"></editLocalStorageEvent>

            <!-- Edit Custom Code Event --> 
            <editCustomCodeEvent v-if="eventForm.type == 'Custom Code'" v-bind="$props" :event="eventForm"></editCustomCodeEvent>
            
            <!-- Edit Revisit Event --> 
            <editRevisitEvent v-if="eventForm.type == 'Revisit'" v-bind="$props" :event="eventForm"></editRevisitEvent>

            <!-- Edit Auto Link Event --> 
            <editAutoLinkEvent v-if="eventForm.type == 'Auto Link'" v-bind="$props" :event="eventForm"></editAutoLinkEvent>

            <!-- Edit Auto Reply Event --> 
            <editAutoReplyEvent v-if="eventForm.type == 'Auto Reply'" v-bind="$props" :event="eventForm"></editAutoReplyEvent>

            <!-- Edit Redirect Event --> 
            <editRedirectEvent v-if="eventForm.type == 'Redirect'" v-bind="$props" :event="eventForm"></editRedirectEvent>

            <!-- Edit Create/Update Account Event --> 
            <editCreateOrUpdateAccountEvent v-if="eventForm.type == 'Create/Update Account'" v-bind="$props" :event="eventForm"></editCreateOrUpdateAccountEvent>

            <div class="border-top pt-3 mt-3">

                <!-- Enter Comment -->
                <commentInput v-model="eventForm.comment" class="mb-2"></commentInput>

                <!-- Highlighter -->
                <span class="d-inline-block mr-2">
                    <span class="font-weight-bold">Highlighter</span>: 
                    <ColorPicker v-model="eventForm.hexColor" recommend></ColorPicker>
                </span>

            </div>

            <!-- Footer -->
            <template v-slot:footer>
                <div class="clearfix">
                    <Button type="primary" @click.native="handleSubmit()" class="float-right">Save Changes</Button>
                    <Button @click.native="closeModal()" class="float-right mr-2">Cancel</Button>
                </div>
            </template>

        </Modal>
    </div>
</template>
<script>

    import activeStateSelector from './../../../../../editor/content/screen/activeStateSelector.vue';
    import modalMixin from './../../../../../../../../../../../components/_mixins/modal/main.vue';
    import commentInput from './../../commentInput.vue';

    //  Get the Event components used to edit
    import editCreateOrUpdateAccountEvent from './create-or-update-account/main.vue';
    import editLocalStorageEvent from './local-storage/main.vue';
    import editBillingApiEvent from './apis/billing-api/main.vue';
    import editNotificationEvent from './notification/main.vue';
    import editCustomCodeEvent from './custom-code/main.vue';
    import editCrudApiEvent from './apis/crud-api/main.vue';
    import editValidationEvent from './validation/main.vue';
    import editFormattingEvent from './formatting/main.vue';
    import editAutoReplyEvent from './auto-reply/main.vue';
    import editAutoLinkEvent from './auto-link/main.vue';
    import editRedirectEvent from './redirect/main.vue';
    import editRevisitEvent from './revisit/main.vue';
    
    export default {
        mixins: [modalMixin],
        components: { 
            activeStateSelector, commentInput, editLocalStorageEvent, editBillingApiEvent, editNotificationEvent, editCrudApiEvent, 
            editValidationEvent, editFormattingEvent, editRedirectEvent, editRevisitEvent, editAutoLinkEvent,
            editAutoReplyEvent, editCustomCodeEvent, editCreateOrUpdateAccountEvent
        },
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
            globalMarkers: {
                type: Array,
                default: () => []
            },
            usingGlobalEventManager: {
                type: Boolean,
                default: false
            }
        },
        data(){
            return {
                eventForm: null,
                eventFormRules: {
                    name: [
                        { required: true, message: 'Please enter your event name', trigger: 'blur' },
                        { min: 3, message: 'Event name is too short', trigger: 'change' },
                        { max: 50, message: 'Event name is too long', trigger: 'change' }
                    ]
                },
            }
        },
        computed: {
            firstRowSpan(){

                if( this.eventForm.active.selected_type == 'conditional' ){

                    return 24;

                }

                return 12;

            },
            modalTitle(){

                if( this.isEditing ){

                    return 'Edit Event';

                }else if( this.isCloning ){

                    return 'Clone Event';
                
                }

            },
            modalOkBtnText(){

                if( this.isEditing ){

                    return 'Save Changes';

                }else if( this.isCloning ){

                    return 'Clone';
                
                }

            },
            modalWidth(){
                return 800;
            }
        },
        methods: {
            
            getEventForm(){

                //  If this is a global event
                if( this.event.global ){

                    var globalEvents = this.version.builder.global_events.filter((globalEvent) => {
                            //  Return Global Events that match the current event id
                            return globalEvent.id == this.event.id;
                    });

                    //  If we have any matching Global Event
                    if( globalEvents.length ){

                        //  Get the first matched Global Event
                        var globalEvent = globalEvents[0];
                
                        //  Copy and return the global event details
                        return _.cloneDeep(globalEvent);

                    }

                }
                
                //  Copy and return the current event details
                return _.cloneDeep(this.event);

            },
            updateGlobalEvent(){

                var globalEventExists = false;

                //  Foreach screen that matches this event, make updates
                var updatedScreens = this.version.builder.screens.map((screen) => {

                    //  Update the screen before repeat events
                    screen.repeat.events.before_repeat = screen.repeat.events.before_repeat.map((event) => {

                        //  If the event ids match
                        if( event.id == this.eventForm.id ){
                            
                            return this.eventForm; 
                        }

                        return event;

                    });

                    //  Update the screen after repeat events
                    screen.repeat.events.after_repeat = screen.repeat.events.after_repeat.map((event) => {

                        //  If the event ids match
                        if( event.id == this.eventForm.id ){
                            
                            return this.eventForm; 
                        }

                        return event;

                    });

                    //  Update the screen display events
                    screen.displays = screen.displays.map((display) => {

                        //  Update the screen display before reply events
                        display.content.events.before_reply = display.content.events.before_reply.map((event) => {

                            //  If the event ids match
                            if( event.id == this.eventForm.id ){
                                
                                return this.eventForm; 

                            }

                            return event;

                        });

                        //  Update the screen display after reply events
                        display.content.events.after_reply = display.content.events.after_reply.map((event) => {

                            //  If the event ids match
                            if( event.id == this.eventForm.id ){
                                
                                return this.eventForm; 

                            }

                            return event;

                        });

                        return display;

                    });

                    return screen;

                });

                //  Update the builder screens with the latest event details
                this.$set(this.version.builder, 'screens', updatedScreens);

                //  Go through each Global Event and update accordingly
                var globalEvents = this.version.builder.global_events.map((globalEvent) => {
                        
                    //  If the Global Event matches the current event id
                    if( globalEvent.id == this.eventForm.id ){

                        globalEventExists = true;

                        //  Update this Global Event
                        globalEvent = this.eventForm;
                    
                    }

                    //  Return the Global Event
                    return globalEvent;

                });

                //  If this event already existed as a Global Event
                if( globalEventExists ){

                    //  Update the Global Events
                    this.$set(this.version.builder, 'global_events', globalEvents);

                //  If this event did not already exist as a Global Event
                }else{

                    //  Add the event to the list of Global Events
                    this.version.builder.global_events.push(this.eventForm);
                    
                }

            },
            handleSubmit(){
                
                //  Validate the event form
                this.$refs['eventForm'].validate((valid) => 
                {   
                    //  If the validation passed
                    if (valid) {

                        if( this.isEditing ){
                        
                            this.handleEditEvent();

                        }else if( this.isCloning ){
                        
                            this.handleCloneEvent();

                        }

                        //  If we are turning this event into a Global event
                        if( this.eventForm.global ){

                            this.updateGlobalEvent();

                        }

                        /** Note the closeModal() method is imported from the
                         *  modalMixin file. It handles the closing process 
                         *  of the modal
                         */
                        this.closeModal();

                    //  If the validation failed
                    } else {
                        this.$Message.warning({
                            content: 'Sorry, you cannot add your event yet',
                            duration: 6
                        });
                    }
                })
            },
            handleEditEvent(){

                //  If this event was set to global before and now its not anymore
                if( this.event.global == true && this.eventForm.global == false){

                    //  Change the event id so that it does not sync with the other global events anymore
                    this.eventForm.id = this.generateEventId();
                    
                }

                //  If the event is set to global and its color its using a normal grey highlight color
                if( this.eventForm.global && this.eventForm.hexColor == '#CECECE' ){
                    //  Change the color to an orange highlight color
                    this.eventForm.hexColor = '#FF9900';
                }
                
                //  If the event is not set to global and its color its using the orange highlight color
                if( !this.eventForm.global && this.eventForm.hexColor == '#FF9900' ){
                    //  Change the color to an grey highlight color
                    this.eventForm.hexColor = '#CECECE';
                }

                this.$set(this.events, this.index, this.eventForm);

                this.$Message.success({
                    content: 'Event updated!',
                    duration: 6
                });

            },
            handleCloneEvent(){

                //  Update the event id
                this.eventForm.id = this.generateEventId();

                //  Add the cloned event to the rest of the other events
                this.events.push(this.eventForm);

                this.$Message.success({
                    content: 'Event cloned!',
                    duration: 6
                });

            },
            generateEventId(){
                return 'event_' + Date.now();
            }
        },
        created(){
            this.eventForm = this.getEventForm();
        }
    }
</script>