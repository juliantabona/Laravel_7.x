<template>

    <List slot="content" size="small">

        <!-- Active State -->
        <ListItem class="p-0">
            <span class="font-weight-bold mr-1">Active: </span>
            <span v-if="event.active.selected_type == 'conditional'">

                <Icon type="ios-code" class="mr-1" size="20" />
                <span>Custom Code</span>

            </span>
            <template v-else>
                <span class="text-capitalize cut-text">{{ event.active.selected_type }}</span>
            </template>
        </ListItem>
        
        <!-- Name -->
        <ListItem class="p-0">
            <span class="font-weight-bold mr-1">Name: </span>
            <span class="cut-text">{{ event.name }}</span>
        </ListItem>

        <!-- Target -->
        <ListItem class="p-0">
            <span class="font-weight-bold mr-1">Target: </span>
            <span v-if="event.event_data.target.code_editor_mode">

                <Icon type="ios-code" class="mr-1" size="20" />
                <span>Custom Code</span>

            </span>
            <template v-else>
                <span v-if="event.event_data.target.text" v-html="event.event_data.target.text" class="cut-text"></span>
                <span v-else :class="isUsingCustomCodeRules ? 'text-info' : 'text-danger'">No Target</span>
            </template>
        </ListItem>

        <!-- Validation Rules -->
        <ListItem class="p-0">
            <span class="font-weight-bold mr-1">Rules: </span>
            <span v-if="numberOfValidationRules" class="cut-text">{{ numberOfValidationRules }} found</span>
                <span v-else class="text-danger">No Rules Found</span>
        </ListItem>

        <ListItem v-if="event.comment" class="list-item-comment">
            <span>
                <Icon type="ios-chatbubbles-outline" :size="20" class="mr-1" />
                <span class="font-weight-bold">Comment: </span><br>{{ event.comment }}
            </span>
        </ListItem>
    
    </List>

</template>

<script>

    export default {
        props: {
            event: {
                type: Object,
                default:() => {}
            }
        },
        data(){
            return {
                
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
            isUsingCustomCodeRules(){
                return this.event.event_data.rules.filter( (rule) => { 

                    //  If this rule uses custom code
                    if( rule.type == 'custom_code' ){
                        //  If this custom code is active
                        if( rule.active.selected_type == 'yes' || rule.active.selected_type == 'conditional'){
                            return true;
                        }
                    }
                    
                    return false;

                }).length ? true : false;

            },
            isValidEvent(){
                /** This event is invalid if:
                 * 
                 *  1) We are not using code editor mode and the target value is not provided unless we have
                 *     a rule that uses custom code to validate
                 *  2) We do not have any rules provided
                 */

                if( (!this.isUsingCustomCodeRules && !this.event.event_data.target.code_editor_mode && !this.event.event_data.target.text) 
                     || !this.numberOfValidationRules){

                    return false;

                }

                return true;
            },
            numberOfValidationRules(){
                return this.event.event_data.rules.length;
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
