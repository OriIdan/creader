// data.js
// This file needs to be changed for different books.
// Note: This file is created automatically

var readerOptions = {flipper: Monocle.Flippers.rtlSlider};

var bookData = Monocle.bookData({
    components: [
      'data/1.html',
      'data/2.html',
      'data/3.html'
    ],
    chapters:[
      {
        title: "The Signal Man",
        src: "data/1.html"
      },
      {
        title: "The Haunted House",
        src: "data/2.html",
      },
      {
        title: "The Trial for Murder",
        src: "data/3.html"
      }
    ],
    metadata: {
      title: "Three Ghost Stories",
      creator: "Charles Dickens"
    }
  });
 
