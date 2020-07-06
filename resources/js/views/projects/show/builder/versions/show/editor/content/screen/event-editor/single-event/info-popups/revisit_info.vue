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

        <!-- Revisit Type -->
        <ListItem class="p-0">
            <span class="font-weight-bold mr-1">Type: </span>
            <span class="cut-text">{{ revisitType }}</span>
        </ListItem>

        <!-- Revisit Trigger -->
        <ListItem class="p-0">
            <span class="font-weight-bold mr-1">Trigger: </span>
            <span class="cut-text">{{ triggerType }}</span>
        </ListItem>

        <!-- Revisit Input -->
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

        <!-- Revisit Screen Link -->
        <ListItem v-if="event.event_data.revisit_type.selected_type == 'screen_revisit'" class="p-0">
            <span class="font-weight-bold mr-1">Screen Link: </span>
            <span v-if="event.event_data.revisit_type.screen_revisit.link.code_editor_mode">
                <Icon type="ios-code" class="mr-1" size="20" />
                <span>Custom Code</span>
            </span>
            <template v-else>
                <span v-if="event.event_data.revisit_type.screen_revisit.link.text" class="d-flex w-100">
                    <template v-if="isValidLink">
                        <Icon type="ios-information-circle-outline" class="text-primary mr-1" size="20" />
                        <span class="text-primary cut-text" v-html="getLinkName"></span>
                    </template>
                    <template v-else>
                        <Icon type="ios-pin-outline" class="text-danger mr-1" size="20" />
                        <span class="text-danger cut-text" v-html="getLinkName"></span>
                    </template>
                </span>
                <span v-else class="text-danger">No Screen Link</span>
            </template>
        </ListItem>

        <!-- Revisit Marker -->
        <ListItem v-if="event.event_data.revisit_type.selected_type == 'marked_revisit'" class="p-0">
            <span class="font-weight-bold mr-1">Marker: </span>
            <div v-if="getMarkerName">
                <Tag type="border" color="success" class="text-wrap">{{ getMarkerName }}</Tag>
            </div>
            <span v-else class="text-danger">No Marker Provided</span>
        </ListItem>

        <!-- Revisit Automatic Replies -->
        <ListItem class="p-0">
            <span class="font-weight-bold mr-1">Automatic Replies: </span>
            <span v-if="event.event_data.general.automatic_replies.code_editor_mode">

                <Icon type="ios-code" class="mr-1" size="20" />
                <span>Custom Code</span>

            </span>
            <template v-else>
                <span v-if="event.event_data.general.automatic_replies.text" class="cut-text" 
                      v-html="event.event_data.general.automatic_replies.text"></span>
                <span v-else class="text-info">No additional replies</span>
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

    var revisitCustomMixin = require('./../../edit-event/revisit/localMixin.js').default;

    export default {
        mixins: [revisitCustomMixin],
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
                /** Note: The "revisitTypes" and "triggerTypes" properties are
                 *  defined within the "revisitCustomMixin"
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
                 *  2) We are using the "Screen Revisit" and the screen link is not valid
                 *  2) We are using the "Marked Revisit" and the marker is not valid
                 * 
                 */
                if( ( this.event.event_data.general.trigger.selected_type == 'manual'
                     && !this.event.event_data.general.trigger.manual.input.code_editor_mode
                     && !this.event.event_data.general.trigger.manual.input.text) ||
                    ( this.event.event_data.revisit_type.selected_type == 'screen_revisit' && !this.isValidLink) ||
                    ( this.event.event_data.revisit_type.selected_type == 'marked_revisit' && !this.getMarkerName) ){

                    return false;

                }
                
                return true;
            },
            revisitType(){
                //  Note: The "revisitTypes" properties are defined within the "revisitCustomMixin"
                for (let index = 0; index < this.revisitTypes.length; index++) {
                    
                    if( this.revisitTypes[index]['value'] == this.event.event_data.revisit_type.selected_type ){
                        
                        return this.revisitTypes[index]['name'];

                    }
                    
                }
            },
            triggerType(){
                //  Note: The "triggerTypes" properties are defined within the "revisitCustomMixin"
                for (let index = 0; index < this.triggerTypes.length; index++) {
                    
                    if( this.triggerTypes[index]['value'] == this.event.event_data.general.trigger.selected_type ){
                        
                        return this.triggerTypes[index]['name'];

                    }
                    
                }
            },
            isValidLink(){

                //  If the link is not written in code
                if( !this.event.event_data.revisit_type.screen_revisit.link.code_editor_mode ){

                    //  If the link is provided
                    if( this.event.event_data.revisit_type.screen_revisit.link.text ){
                            
                        //  If we have a matching screen return true otherwise false
                        var screens = this.builder.screens.filter( (screen) => {
                            
                            return ( screen.id == this.event.event_data.revisit_type.screen_revisit.link.text ) ? true : false;

                        });

                        if( screens.length ){
                            
                            //  If the screen we are linking to is not the current screen
                            if( this.screen.id != screens[0]['id'] ){

                                return true;

                            }else{

                                return false;

                            }

                        }else{

                            return false;

                        }

                    }else{
                        
                        return false;

                    }

                }

                //  Otherwise return true
                return true;
            },
            getLinkName(){
                    
                //  If we have a matching screen return true otherwise false
                var screens = this.builder.screens.filter( (screen) => {
                    
                    return ( screen.id == this.event.event_data.revisit_type.screen_revisit.link.text ) ? true : false;

                });

                if( screens.length ){
                    
                    //  If the screen we are linking to is not the current screen
                    if( this.screen.id != screens[0]['id'] ){

                        return 'Screens / ' + screens[0]['name'];

                    }else{

                        return 'Screens / ' + screens[0]['name'] + '<br>Linking to the same screen';

                    }

                }else{

                    return 'Screens / ' + 'Unknown Screen';

                }

            },
            getMarkerName(){
                for (let index = 0; index < this.globalMarkers.length; index++) {
                    
                    if( this.globalMarkers[index]['text'] == this.event.event_data.revisit_type.marked_revisit.selected_marker ){
                        return this.globalMarkers[index]['text'];
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
