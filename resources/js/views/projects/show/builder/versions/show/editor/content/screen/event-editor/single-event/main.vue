<template>

    <Card :class="cardClasses" :style="cardStyles">
        
        <!-- Event Title -->
        <div slot="title" class="cursor-pointer" @click="toggleExpansion()">
            
            <Row>

                <Col :span="14" class="d-flex">

                    <!-- Expand / Collapse Icon  -->
                    <Icon :type="arrowDirection" 
                          class="text-primary cursor-pointer mr-2" :size="20" 
                          :style="{ marginTop: '-3px' }" @click.stop="toggleExpansion()" />

                    <span class="single-draggable-item-title d-block font-weight-bold cut-text">{{ event.name }}</span>

                </Col>

                <Col class="d-flex" :span="10">
                
                    <!-- Failed Link Warning -->
                    <Poptip trigger="hover" width="350" placement="top" word-wrap>

                        <template slot="content">
                            <crudApiInfo v-if="event.type == 'CRUD API'" v-bind="$props" @updateIsValidEvent="isValidEvent = $event"></crudApiInfo>
                            <validationInfo v-else-if="event.type == 'Validation'" v-bind="$props" @updateIsValidEvent="isValidEvent = $event"></validationInfo>
                            <formattingInfo v-else-if="event.type == 'Formatting'" v-bind="$props" @updateIsValidEvent="isValidEvent = $event"></formattingInfo>
                            <localStorageInfo v-else-if="event.type == 'Local Storage'" v-bind="$props" @updateIsValidEvent="isValidEvent = $event"></localStorageInfo>
                            <revisitInfo v-else-if="event.type == 'Revisit'" v-bind="$props" @updateIsValidEvent="isValidEvent = $event"></revisitInfo>
                            <redirectInfo v-else-if="event.type == 'Redirect'" v-bind="$props" @updateIsValidEvent="isValidEvent = $event"></redirectInfo>
                        </template>
                        

                        <Icon v-if="isValidEvent" type="ios-information-circle-outline" class="text-primary mr-1" :style="{ marginTop: '-5px' }" size="30" />
                        <Icon v-else type="ios-alert-outline" class="text-danger mr-1" :style="{ marginTop: '-5px' }" size="30" />

                    </Poptip>

                    <!-- Active Status -->
                    <div :style="{ marginTop: '-4px' }">
                        <Tag v-if="event.active.code_editor_mode" type="border" color="cyan">Active Conditionally</Tag>
                        <Tag v-else type="border" :color="event.active.text ? 'green' : 'warning'">{{ event.active.text ? 'Active' : 'InActive' }}</Tag>
                    </div>

                     <!-- Event Type -->
                    <div :style="{ marginTop: '-4px' }">
                        
                        <Tag type="border" color="cyan">

                            <!-- Event Icon -->
                            <eventIcon :eventType="event.type" :size="20" class="mr-1"></eventIcon>

                            <!-- Event Type -->
                            <span>{{ event.type }}</span>

                            <template v-if="hasValidationRules">
                                | <span class="font-weight-bold">{{ numberOfValidationRules }} {{ numberOfValidationRules == '1' ? 'Rule' : 'Rules' }}</span> 
                            </template>

                        </Tag>

                    </div>

                </Col>
            </Row>
            
        </div>

        <!-- Event Toolbar (Edit, Move, Delete Buttons) -->
        <div slot="extra">

            <div class="single-draggable-item-toolbox">

                <!-- Remove Event Button  -->
                <Icon type="ios-trash-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleConfirmRemoveEvent()" />

                <!-- Edit Event Button  -->
                <Icon type="ios-create-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleEditEvent()" />

                <!-- Copy Event Button  -->
                <Icon type="ios-copy-outline" class="single-draggable-item-icon mr-2" size="20" @click="handleCloneEvent()"/>

                <!-- Move Event Button  -->
                <Icon type="ios-move" class="single-draggable-item-icon dragger-handle mr-2" size="20" />
            
            </div> 

        </div>  

        <div v-show="isExpanded">

            <!-- Event Details  -->

            <!-- Comment -->
            <span class="d-flex">
                <Icon type="ios-chatbubbles-outline" :size="20" class="mr-1" />
                <span class="font-weight-bold mr-2">Comment: </span><br>
                <span v-if="event.comment">{{ event.comment }}</span>
                <span v-else class="text-info">No comment</span>
            </span>

        </div>

        <!-- 
            MODAL TO ADD / CLONE / EDIT EVENT
        -->
        <template v-if="isOpenManageEventModal">

            <manageEventModal
                :index="index"
                :event="event"
                :events="events"
                :screen="screen"
                :display="display"
                :builder="builder"
                :isCloning="isCloning"
                :isEditing="isEditing"
                :globalMarkers="globalMarkers"
                @visibility="isOpenManageEventModal = $event">
            </manageEventModal>

        </template>

    </Card>

</template>

<script>

    import manageEventModal from './../edit-event/manageEventModal.vue';
    import localStorageInfo from './info-popups/local_storage_info.vue'
    import validationInfo from './info-popups/validation_info.vue'
    import formattingInfo from './info-popups/formatting_info.vue'
    import revisitInfo from './info-popups/revisit_info.vue'
    import redirectInfo from './info-popups/redirect_info.vue'

    
    
    import crudApiInfo from './info-popups/crud_api_info.vue';
    import eventIcon from './../eventIcon.vue';

    export default {
        components: { 
            manageEventModal, localStorageInfo, validationInfo, formattingInfo, crudApiInfo, revisitInfo, 
            redirectInfo, eventIcon },
        props: {
            index: {
                type: Number,
                default:null
            },
            event: {
                type: Object,
                default:() => {}
            },
            events: {
                type: Array,
                default:() => []
            },
            display: {
                type: Object,
                default:() => {}
            },
            screen: {
                type: Object,
                default:() => {}
            },
            builder: {
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
                isOpenManageEventModal: false,
                isValidEvent: true,
                isExpanded: false,
                isEditing: false,
                isCloning: false
            }
        },
        computed: {
            cardStyles(){
                return {
                    borderLeft: '4px solid ' + this.event.hexColor
                }
            },
            cardClasses(){
                return [
                    'single-draggable-item', 
                    (this.isExpanded ? 'active' : ''), 'mb-2'
                ]
            },
            arrowDirection(){
                return this.isExpanded ? 'ios-arrow-down' : 'ios-arrow-forward';
            },
            numberOfValidationRules(){
                return ((this.event.event_data || {}).rules || []).length;
            },
            hasValidationRules(){
                return this.numberOfValidationRules ? true : false;
            }
        },
        methods: {
            toggleExpansion(){
                this.isExpanded = !this.isExpanded;
            },
            handleEditEvent(){
                this.isCloning = false;
                this.isEditing = true;
                this.handleOpenManageEventModal();
            },
            handleCloneEvent() {
                this.isCloning = true;
                this.isEditing = false;
                this.handleOpenManageEventModal();
            },
            handleConfirmRemoveEvent(){

                const self = this;

                //  Make a popup confirmation modal so that we confirm the event removal
                this.$Modal.confirm({
                    width: '450',
                    closable: true,
                    okText: 'Delete',
                    cancelText: 'Cancel',
                    title: 'Delete Event',
                    onOk: () => { this.handleRemoveEvent() },
                    render: function (h) {
                        return h(
                            'span', [
                                'Are you sure you want to delete "',
                                h('span', { class: ['font-weight-bold'] }, self.event.name),
                                '". After this event is deleted you cannot recover it again.'
                            ]
                        )
                    }
                });
            },
            handleRemoveEvent() {

                //  Remove event from list
                this.events.splice(this.index, 1);

                //  Event removed success message
                this.$Message.success({
                    content: 'Event removed!',
                    duration: 6
                });
            },
            handleOpenManageEventModal(){
                this.isOpenManageEventModal = true;
            }
        }
    }

</script>
