// Custom defined mixin object
var mixin = {
    data(){
        return{
            storage_types:[
                {
                    name: 'String',
                    value: 'string'
                },
                {
                    name: 'Array',
                    value: 'array'
                },
                {
                    name: 'Code Editor',
                    value: 'code'
                }
            ],
            array_datasets:[
                {
                    name: 'Array Values',
                    value: 'values'
                },
                {
                    name: 'Array Key/Values',
                    value: 'key_values'
                }
            ],
            array_modes: [
                {
                    name: 'Array Replace',
                    value: 'replace'
                },
                {
                    name: 'Array Append (Insert After)',
                    value: 'append'
                },
                {
                    name: 'Array Prepend (Insert Before)',
                    value: 'prepend'
                }
            ],
            string_modes: [
                {
                    name: 'String Replace',
                    value: 'replace'
                },
                {
                    name: 'String Join (Concatenate)',
                    value: 'concatenate'
                }
            ],
            code_modes: [
                {
                    name: 'Array Replace',
                    value: 'replace'
                },
                {
                    name: 'Array Append (Insert After)',
                    value: 'append'
                },
                {
                    name: 'Array Prepend (Insert Before)',
                    value: 'prepend'
                },
                {
                    name: 'String Join (Concatenate)',
                    value: 'concatenate'
                }
            ],
            defaultTypes: [
                {
                    name: 'Custom',
                    value: 'custom'
                },
                {
                    name: 'True',
                    value: 'true'
                },
                {
                    name: 'False',
                    value: 'false'
                },
                {
                    name: 'Null',
                    value: 'null'
                },
                {
                    name: 'Empty Array',
                    value: 'empty_array'
                }
            ]
        }
    }
}

export default mixin;