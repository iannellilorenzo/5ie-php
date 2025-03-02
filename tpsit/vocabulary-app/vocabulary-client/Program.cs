using System;
using System.Net.Sockets;
using System.Text;
using System.Text.Json;
using System.Collections.Generic;

namespace VocabularyClient
{
    class Program
    {
        static void Main(string[] args)
        {
            Console.WriteLine("Vocabulary Client");
            Console.WriteLine("----------------");
            
            try
            {
                while (true)
                {
                    Console.WriteLine("\nOptions:");
                    Console.WriteLine("1. Look up a word");
                    Console.WriteLine("2. Add a new word");
                    Console.WriteLine("3. List all words");
                    Console.WriteLine("4. Exit");
                    Console.Write("\nEnter your choice (1-4): ");
                    
                    string choice = Console.ReadLine();
                    
                    switch (choice)
                    {
                        case "1":
                            LookupWord();
                            WaitForKeyAndClear();
                            break;
                        case "2":
                            AddWord();
                            WaitForKeyAndClear();
                            break;
                        case "3":
                            ListWords();
                            WaitForKeyAndClear();
                            break;
                        case "4":
                            return;
                        default:
                            Console.WriteLine("Invalid choice, please try again.");
                            WaitForKeyAndClear();
                            break;
                    }
                }
            }
            catch (Exception e)
            {
                Console.WriteLine("Error: " + e.Message);
                Console.WriteLine("Press any key to exit...");
                Console.ReadKey();
            }
        }
        
        // Metodo per attendere la pressione di un tasto e pulire lo schermo
        static void WaitForKeyAndClear()
        {
            Console.WriteLine("\nPress any key to continue...");
            Console.ReadKey();
            Console.Clear();
            Console.WriteLine("Vocabulary Client");
            Console.WriteLine("----------------");
        }
        
        static void LookupWord()
        {
            Console.Write("Enter the word to look up: ");
            string word = Console.ReadLine();
            
            if (string.IsNullOrWhiteSpace(word))
            {
                Console.WriteLine("Word cannot be empty.");
                return;
            }
            
            var request = new Dictionary<string, string>
            {
                { "action", "lookup" },
                { "word", word }
            };
            
            string response = SendRequest(JsonSerializer.Serialize(request));
            DisplayResponse(response);
        }
        
        static void AddWord()
        {
            Console.Write("Enter the word: ");
            string word = Console.ReadLine();
            
            if (string.IsNullOrWhiteSpace(word))
            {
                Console.WriteLine("Word cannot be empty.");
                return;
            }
            
            Console.Write("Enter the definition: ");
            string definition = Console.ReadLine();
            
            if (string.IsNullOrWhiteSpace(definition))
            {
                Console.WriteLine("Definition cannot be empty.");
                return;
            }
            
            var request = new Dictionary<string, string>
            {
                { "action", "add" },
                { "word", word },
                { "definition", definition }
            };
            
            string response = SendRequest(JsonSerializer.Serialize(request));
            DisplayResponse(response);
        }
        
        static void ListWords()
        {
            var request = new Dictionary<string, string>
            {
                { "action", "list" }
            };
            
            string response = SendRequest(JsonSerializer.Serialize(request));
            
            try
            {
                var responseObj = JsonSerializer.Deserialize<Dictionary<string, JsonElement>>(response);
                
                if (responseObj.ContainsKey("status") && responseObj["status"].GetString() == "success")
                {
                    Console.WriteLine("\nVocabulary List:");
                    Console.WriteLine("---------------");
                    
                    var words = responseObj["words"].EnumerateObject();
                    foreach (var item in words)
                    {
                        Console.WriteLine($"{item.Name}: {item.Value.GetString()}");
                    }
                }
                else
                {
                    DisplayResponse(response);
                }
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Error parsing response: {ex.Message}");
            }
        }
        
        static string SendRequest(string request)
        {
            try
            {
                // Create a TcpClient
                TcpClient client = new TcpClient("127.0.0.1", 13000);
                
                // Get a client stream for reading and writing
                NetworkStream stream = client.GetStream();
                
                // Convert the request string to byte array
                byte[] data = Encoding.UTF8.GetBytes(request);
                
                // Send the message to the connected TcpServer
                stream.Write(data, 0, data.Length);
                
                // Receive the server response
                data = new byte[1024];
                int bytesRead = stream.Read(data, 0, data.Length);
                string response = Encoding.UTF8.GetString(data, 0, bytesRead);
                
                // Close everything
                stream.Close();
                client.Close();
                
                return response;
            }
            catch (Exception e)
            {
                return JsonSerializer.Serialize(new { status = "error", message = $"Connection error: {e.Message}" });
            }
        }
        
        static void DisplayResponse(string response)
        {
            try
            {
                var responseObj = JsonSerializer.Deserialize<Dictionary<string, string>>(response);
                
                if (responseObj.ContainsKey("status"))
                {
                    if (responseObj["status"] == "success")
                    {
                        Console.ForegroundColor = ConsoleColor.Green;
                        
                        if (responseObj.ContainsKey("word") && responseObj.ContainsKey("definition"))
                        {
                            Console.WriteLine($"\n{responseObj["word"]}: {responseObj["definition"]}");
                        }
                        else if (responseObj.ContainsKey("message"))
                        {
                            Console.WriteLine($"\n{responseObj["message"]}");
                        }
                    }
                    else
                    {
                        Console.ForegroundColor = ConsoleColor.Red;
                        Console.WriteLine($"\nError: {responseObj["message"]}");
                    }
                }
                else
                {
                    Console.WriteLine("\nInvalid response format from server.");
                }
                
                Console.ResetColor();
            }
            catch (Exception ex)
            {
                Console.WriteLine($"Error displaying response: {ex.Message}");
            }
        }
    }
}