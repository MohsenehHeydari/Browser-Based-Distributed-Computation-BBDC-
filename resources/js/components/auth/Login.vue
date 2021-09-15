<template>

    <div>
        <p class="login-box-msg">Sign in to start your session</p>

        <form action="" method="post" @submit.prevent="submitForm" @keydown="deleteError($event.target.name)">
            <div class="input-group mb-3">
                <input name="email" autocomplete="off"
                       v-model="form.email" type="email" class="form-control" placeholder="Email">
                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-envelope"></span>
                    </div>
                </div>
                <template v-if="errors.email !== undefined">
                    <div class="error invalid-feedback" v-for="error in errors.email">{{error}}</div>
                </template>
            </div>
            <div class="input-group mb-3">
                <input name="password" v-model="form.password" type="password" class="form-control" placeholder="Password">
                <div class="input-group-append">
                    <div class="input-group-text">
                        <span class="fas fa-lock"></span>
                    </div>
                </div>
                <template v-if="errors.password !== undefined">
                    <div class="error invalid-feedback" v-for="error in errors.password">{{error}}</div>
                </template>
            </div>
            <div class="row">
                <div class="col-8">
                    <div class="icheck-primary">
                        <input name="remember" type="checkbox" id="remember" v-model="form.remember">
                        <label for="remember">
                            Remember Me
                        </label>
                    </div>
                </div>
                <!-- /.col -->
                <div class="col-4">
                    <button type="submit" class="btn btn-primary btn-block">Sign In</button>
                </div>
                <!-- /.col -->
            </div>
        </form>
        <p class="mb-0">
            <a @click.prevent="$emit('change_component','register')" class="text-center">Register a new membership</a>
        </p>
        <div class="alert alert-solid-danger alert-bold" style="display:block;" v-if="errors.server_error !== undefined">
            <div> server error</div>
        </div>

    </div>

</template>

<script>
    import axios from 'axios';
    export default {
        data() {
            return {
                component:'login',
                form: {
                    email: '',
                    password: '',
                    remember: null,
                },
                errors: {}
            }
        },
        inject:['baseUrl'],
        props:['type'],

        methods: {
            submitForm(event) {
                this.errors=[];
                let url = this.baseUrl + '/' + this.type + '/login';
                let el = event.target;
                let form_data = new FormData(el);
                form_data.append('_method', 'post');
                axios.post(url,form_data)
                    .then(response => {
                        if(response.data.redirect_path !== undefined){
                            window.location=response.data.redirect_path;
                        }
                    })
                    .catch(error => {

                        if (error.response !== undefined && error.response.status === 422) {
                            if (error.response.data.errors !== undefined) {
                                this.errors = error.response.data.errors;
                            }
                        }

                    })
            },
            deleteError(error) {
                if (this.errors[error] !== undefined) {
                    delete this.errors[error];
                }

            },
        }
    }
</script>
