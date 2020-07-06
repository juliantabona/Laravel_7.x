<template>

    <List slot="content" size="small">

        <!-- Active State -->
        <ListItem class="p-0">
            <span class="font-weight-bold mr-1">Active: </span>
            <span v-if="event.active.code_editor_mode">

                <Icon type="ios-code" class="mr-1" size="20" />
                <span>Custom Code</span>

            </span>
            <template v-else>
                <span class="cut-text">{{ event.active.text ? 'Yes' : 'No' }}</span>
            </template>
        </ListItem>
        
        <!-- Name -->
        <ListItem class="p-0">
            <span class="font-weight-bold mr-1">Name: </span>
            <span class="cut-text">{{ event.name }}</span>
        </ListItem>

        <!-- Redirect Trigger -->
        <ListItem class="p-0">
            <span class="font-weight-bold mr-1">Trigger: </span>
            <span class="cut-text">{{ triggerType }}</span>
        </ListItem>

        <!-- Redirect Input -->
        <ListItem v-if="event.event_data.general.trigger.selected_type == 'manual'" class="p-0">
            <span class="font-weight-bold mr-1">Trigger Input: </span>
            <span v-if="event.event_data.general.trigger.manual.input.code_editor_mode">

                <Icon type="ios-code" class="mr-1" size="20" />
                <span>Custom Code</span>

            </span>
            <template v-else>
                <span v-if="event.event_data.general.trigger.manual.input.text" 
                      v-html="event.event_data.general.trigger.manual.input.text" class="cut-text"></span>
                <span v-else class="text-danger">No Input Provided</span>
            </template>
        </ListItem>

        <!-- Service Code -->
        <ListItem class="p-0">
            <span class="font-weight-bold mr-1">Service Code: </span>
            <span v-if="event.event_data.service_code.code_editor_mode">

                <Icon type="ios-code" class="mr-1" size="20" />
                <span>Custom Code</span>

            </span>
            <template v-else>
                <span v-if="event.event_data.service_code.text" 
                      v-html="event.event_data.service_code.text" class="cut-text"></span>
                <span v-else class="text-danger">No Service Code Provided</span>
            </template>
        </ListItem>

        <!-- Comment -->
        <ListItem v-if="event.comment" class="list-item-comment">
            <span>
                <Icon type="ios-chatbubbles-outline" :size="20" class="mr-1" />
                <span class="font-weight-bold">Comment: </span><br>{{ event.comment }}
            </span>
        </ListItem>
    
    </List>

</template>

<script>

    var redirectCustomMixin = require('./../../edit-event/redirect/localMixin.js').default;

    export default {
        mixins: [redirectCustomMixin],
        props: {
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
                /** Note: The "triggerTypes" properties are
                 *  defined within the "redirectCustomMixin"
                 * 
                */
            }
        },
        watch: {

            //  Watch for changes on the isValidEvent
            isValidEvent: {
                handler: function (val, oldVal) {

                    //  Emit the isValidEvent the parent value
                    this.emitIsValidEvent();

                }
            }
        },
        computed: {
            isValidEvent(){
                /** This event is invalid if:
                 * 
                 *  1) We are using a trigger input without code editor mode and the input value is not provided
                 *  2) The service code is not provided
                 * 
                 */
                if( ( this.event.event_data.general.trigger.selected_type == 'manual'
                     && !this.event.event_data.general.trigger.manual.input.code_editor_mode
                     && !this.event.event_data.general.trigger.manual.input.text) || 
                    ( !this.event.event_data.service_code.code_editor_mode &&
                        !this.event.event_data.service_code.text ) 
                ){

                    return false;

                }
                
                return true;
            },
            triggerType(){
                //  Note: The "triggerTypes" properties are defined within the "redirectCustomMixin"
                for (let index = 0; index < this.triggerTypes.length; index++) {
                    
                    if( this.triggerTypes[index]['value'] == this.event.event_data.general.trigger.selected_type ){
                        
                        return this.triggerTypes[index]['name'];

                    }
                    
                }
            }
        },
        methods: {
            emitIsValidEvent(){

                //  Update the parent value
                this.$emit('updateIsValidEvent', this.isValidEvent);

            }
        },
        created(){
            
            //  Emit the isValidEvent the parent value
            this.emitIsValidEvent();

        }
    }

</script>
