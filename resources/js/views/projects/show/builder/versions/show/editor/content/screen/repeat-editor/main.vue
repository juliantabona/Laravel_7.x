<template>

    <!-- Repeat Screen Events -->
    <Row>

        <Col :span="24">

            <div class="border-bottom-dashed mt-3 pb-3 mb-3">

                <!-- Show active state checkbox (Marks if this is active / inactive) -->
                <activeStateCheckbox v-model="screen.repeat.active" sampleCodeTemplate="ussd_service_select_option_display_name_sample_code"></activeStateCheckbox>

            </div>

            <!-- Repeat Screen Type -->
            <div class="d-flex mb-3">

                <span class="font-weight-bold text-dark mt-1 mr-2">Repeat Type:</span>

                <Select v-model="screen.repeat.selected_type" style="width: 200px;">

                    <Option 
                        v-for="(repeatType, key) in repeatTypes"
                        :key="key" :value="repeatType.value" :label="repeatType.name">
                    </Option>

                </Select>

            </div>

            <!-- Repeat On Items Settings -->
            <repeatOnItemsSettings v-if="screen.repeat.selected_type == 'repeat_on_items'" :screen="screen" :builder="builder"></repeatOnItemsSettings>
            
            <!-- Repeat On Number Settings -->
            <repeatOnNumberSettings v-if="screen.repeat.selected_type == 'repeat_on_number'" :screen="screen" :builder="builder"></repeatOnNumberSettings>

            <!-- Repeat Events -->
            <repeatEventManager :globalMarkers="globalMarkers" :screen="screen" :builder="builder"></repeatEventManager>
        
        </Col>

    </Row>
    
</template>

<script>

    import repeatEventManager from './repeat-event-manager/main.vue';
    import repeatOnNumberSettings from './repeat-on-number/main.vue';
    import repeatOnItemsSettings from './repeat-on-items/main.vue';
    import activeStateCheckbox from './../activeStateCheckbox.vue';

    export default {
        components: { repeatEventManager, repeatOnNumberSettings, repeatOnItemsSettings, activeStateCheckbox },
        props: {
            screen: {
                type: Object,
                default: null
            },
            builder: {
                type: Object,
                default: null
            },
            globalMarkers: {
                type: Array,
                default: () => []
            }
        },
        data(){
            return {
                repeatTypes: [
                    {
                        name: 'Repeat on number',
                        value: 'repeat_on_number'
                    },
                    {
                        name: 'Repeat on items',
                        value: 'repeat_on_items'
                    },
                    {
                        name: 'Custom Repeat',
                        value: 'custom_repeat'
                    },
                ]
            }
        }
    };
  
</script>