<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Alpine JS</title>
        @vite('resources/js/welcome/index.js')
    </head>
    <body>
        <!-- x-text, x-html -->
        <div x-data="{name: 'Efrain Losada', message: 'Hello <b>World!</b>'}">
          <p x-text="name"></p>
          <p x-html="message"></p>
        </div>
        <!-- x-data with methods -->
        <div x-data="{message: 'Click to Change', change(){ 
          this.message = 'The text was changed'
        }}">
          <p x-text="message" @click="change()"></p>
        </div>
        <!-- x-data reusable data -->
        <div x-data="dropdown">
          <button @click="toggle">Open/Close</button>
          <div x-show="open">
            Content...
          </div>
        </div>
        <div x-data="dropdown">
          <button @click="toggle">Open/Close 2</button>
          <div x-show="open">
            Content 2...
          </div>
        </div>
        <!-- Data-Less Components -->
        <div x-data @click="alert('Hello World')">
          Click Me
        </div>
        <!-- Data comming from Store -->
        <div x-data x-text="$store.currentUser.username">
          Click Me
        </div>
        <!-- x-init -->
        <div x-init="console.log('Init')"></div>
        <div x-data="{
          init() {
            console.log('Other init')
          }
        }"></div>
        <!-- x-for -->
        <div x-data="{posts: [
          {id: 1, title: 'Lorem ipsum'}
          ,{id: 2, title: 'Dolor sit amed'}
        ]}">
          <template x-for="p in posts" :key="p.id">
            <h2 x-text="p.title"></h2>
          </template>
        </div>

        <!-- Challenge -->
        <div x-data="{colors: ['red', 'green', 'blue']}">
          <template x-for='c in colors'>
            <div style="width: 40px; height: 40px; display: inline-block" :style="{backgroundColor: c}"></div>
          </template>
        </div>

        <!-- x-model -->
        <div x-data="{message: ''}">
          <input type='text' x-model='message' />
          <p x-text='message'></p>
        </div>

        <!-- Chagenge 2 -->
        <div x-data="{buttonText: 'Button Text', backgroundColor: 'white', buttonId: ''}">
          <div>
            <label>Input 1</label>
            <input type='text' x-model="buttonText"/>
          </div>
          <div>
            <label>Input 2</label>
            <input type='text' x-model='backgroundColor'/>
          </div>
          <div>
            <label>Input 3</label>
            <input type='text' x-model='buttonId' />
          </div>
          <button :id="buttonId" x-text="buttonText" :style="{backgroundColor: backgroundColor}"></button>
        </div>

    </body>
</html>
