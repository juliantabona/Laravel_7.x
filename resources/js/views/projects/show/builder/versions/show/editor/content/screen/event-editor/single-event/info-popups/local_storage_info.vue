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

        <!-- Reference -->
        <ListItem class="p-0">
            <span class="font-weight-bold mr-1">Reference: </span>
            <span v-if="event.event_data.reference_name" class="cut-text">{{ event.event_data.reference_name }}</span>
            <span v-else class="text-danger">No Reference Name</span>
        </ListItem>

        <!-- Storage Type -->
        <ListItem class="p-0">
            <span class="font-weight-bold mr-1">Storage: </span>
            <span class="cut-text">{{ storageType }}</span>
        </ListItem>

        <!-- Array Structure Type -->
        <ListItem v-if="event.event_data.storage.selected_type == 'array'" class="p-0">
            <span class="font-weight-bold mr-1">Structure: </span>
            <span class="cut-text">{{ arrayStructureType }}</span>
        </ListItem>

        <!-- Storage Mode -->
        <ListItem class="p-0">

            <span class="font-weight-bold mr-1">Mode: </span>

            <!-- Array Storage Mode -->
            <span v-if="event.event_data.storage.selected_type == 'array'" class="cut-text">{{ arrayMode }}</span>

            <!-- String Storage Mode -->
            <span v-else-if="event.event_data.storage.selected_type == 'string'" class="cut-text">{{ stringMode }}</span>

            <!-- Code Storage Mode -->
            <span v-else-if="event.event_data.storage.selected_type == 'code'" class="cut-text">{{ codeMode }}</span>

        </ListItem>

        <ListItem v-if="event.event_data.storage.selected_type == 'array'" class="p-0">
            
            <!-- Total Array Values -->
            <template v-if="event.event_data.storage.array.dataset.selected_type == 'values'">
                <span class="font-weight-bold mr-1">Values: </span>
                <span v-if="numberOfArrayValues" class="cut-text">{{ numberOfArrayValues }} found</span>
                <span v-else class="text-danger">No Values Found</span>
            </template>

            <!-- Total Array Key/Values -->
            <template v-else-if="event.event_data.storage.array.dataset.selected_type == 'key_values'">
                <span class="font-weight-bold mr-1">Key/Values: </span>
                <span v-if="numberOfArrayKeyValues" class="cut-text">{{ numberOfArrayKeyValues }} found</span>
                <span v-else class="text-danger">No Key/Values Found</span>
            </template>

        </ListItem>

        <template v-if="event.event_data.storage.selected_type == 'string'">

            <ListItem class="p-0">
                
                <!-- String Separator -->
                <span class="font-weight-bold mr-1">String: </span>
                <span v-if="event.event_data.storage.string.dataset.code_editor_mode">

                    <Icon type="ios-code" class="mr-1" size="20" />
                    <span>Custom Code</span>

                </span>
                <template v-else>
                    <span v-if="event.event_data.storage.string.dataset.text"
                          v-html="event.event_data.storage.string.dataset.text" class="cut-text">
                    </span>
                    <span v-else class="text-danger">No String/Text Provided</span>
                </template>

            </ListItem>

            <ListItem class="p-0">
                
                <!-- String Separator -->
                <template v-if="event.event_data.storage.string.mode.selected_type == 'concatenate'">
                    <span class="font-weight-bold mr-1">Join: </span>
                    <span v-if="joinStringCharacter" v-html="joinStringCharacter" class="cut-text"></span>
                    <span v-else class="text-danger">No Join Provided</span>
                </template>

            </ListItem>

        </template>

        <ListItem v-if="event.comment" class="list-item-comment">
            <span>
                <Icon type="ios-chatbubbles-outline" :size="20" class="mr-1" />
                <span class="font-weight-bold">Comment: </span><br>{{ event.comment }}
            </span>
        </ListItem>
    
    </List>

</template>

<script>

    var localStorageCustomMixin = require('./../../edit-event/local-storage/localMixin.js').default;

    export default {
        mixins: [localStorageCustomMixin],
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
            isValidEvent(){
                /** This event is invalid if:
                 * 
                 *  1) We do not have the reference name provided
                 *  2) We are using the Array Storage type with the "values" dataset and we don't have values
                 *  3) We are using the Array Storage type with the "key/values" dataset and we don't have key/values
                 *  4) We are using the String Storage type without code editor mode and we don't have a string/text provided
                 *  5) We are using the String Storage type with the "Concatenate" mode and we don't have a joining character e.g ","
                 *  
                 */
                if( !this.event.event_data.reference_name || 
                    ( this.event.event_data.storage.selected_type == 'array' 
                      && this.event.event_data.storage.array.dataset.selected_type == 'values' && !this.numberOfArrayValues ) || 
                    ( this.event.event_data.storage.selected_type == 'array' 
                      && this.event.event_data.storage.array.dataset.selected_type == 'key_values' && !this.numberOfArrayKeyValues ) ||
                    ( this.event.event_data.storage.selected_type == 'string' 
                      && !this.event.event_data.storage.string.dataset.code_editor_mode && !this.event.event_data.storage.string.dataset.text ) ||
                    ( this.event.event_data.storage.selected_type == 'string' 
                      && this.event.event_data.storage.string.mode.selected_type == 'concatenate' && !this.joinStringCharacter) ){

                    return false;

                }

                return true;
            },
            storageType(){
                //  Note: "storage_types" is defind within "localStorageCustomMixin"
                for (let index = 0; index < this.storage_types.length; index++) {
                    
                    if( this.storage_types[index]['value'] == this.event.event_data.storage.selected_type ){
                        
                        return this.storage_types[index]['name'];

                    }
                    
                }
            },
            arrayStructureType(){
                //  Note: "array_datasets" is defind within "localStorageCustomMixin"
                for (let index = 0; index < this.array_datasets.length; index++) {
                    
                    if( this.array_datasets[index]['value'] == this.event.event_data.storage.array.dataset.selected_type ){
                        
                        return this.array_datasets[index]['name'];

                    }
                    
                }
            },
            arrayMode(){
                //  Note: "array_modes" is defind within "localStorageCustomMixin"
                for (let index = 0; index < this.array_modes.length; index++) {
                    
                    if( this.array_modes[index]['value'] == this.event.event_data.storage.array.mode.selected_type ){
                        
                        return this.array_modes[index]['name'];

                    }
                    
                }
            },
            stringMode(){
                //  Note: "string_modes" is defind within "localStorageCustomMixin"
                for (let index = 0; index < this.string_modes.length; index++) {
                    
                    if( this.string_modes[index]['value'] == this.event.event_data.storage.string.mode.selected_type ){
                        
                        return this.string_modes[index]['name'];

                    }
                    
                }
            },
            codeMode(){
                //  Note: "code_modes" is defind within "localStorageCustomMixin"
                for (let index = 0; index < this.code_modes.length; index++) {
                    
                    if( this.code_modes[index]['value'] == this.event.event_data.storage.code.mode.selected_type ){
                        
                        return this.code_modes[index]['name'];

                    }
                    
                }
            },
            numberOfArrayValues(){
                return this.event.event_data.storage.array.dataset.values.length;
            },
            numberOfArrayKeyValues(){
                return this.event.event_data.storage.array.dataset.key_values.length;
            },
            joinStringCharacter(){
                return this.event.event_data.storage.string.mode.concatenate.value;
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
